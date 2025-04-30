<?php

namespace Plugin\Saas\Http\Controllers\Admin;


use DateTime;
use Exception;
use Carbon\Carbon;
use Core\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Plugin\Saas\Models\Package;
use Illuminate\Support\Facades\DB;
use Plugin\Saas\Models\SaasAccount;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Plugin\Saas\Models\CustomDomain;
use Core\Repositories\UserRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Plugin\Saas\Repositories\CouponPackageRepository;
use Plugin\Saas\Repositories\PaymentRepository;
use Plugin\Saas\Repositories\SubscriptionRepository;

class CustomerController extends Controller
{
    protected $user_repository;
    public $sub_repo;
    public $cp_repo;

    public function __construct(UserRepository $user_repository, SubscriptionRepository $sub_repo, CouponPackageRepository $cp_repo)
    {
        $this->user_repository = $user_repository;
        $this->sub_repo = $sub_repo;
        $this->cp_repo = $cp_repo;
    }
    /**
     * Will redirect to customer list page
     */
    public function index()
    {
        $customers = DB::table('tl_users')
            ->leftJoin('tl_saas_accounts', 'tl_saas_accounts.user_id', '=', 'tl_users.id')
            ->where('tl_users.user_type', '=', config('saas.user_type.subscriber'))
            ->orderBy('tl_users.id', 'desc')
            ->groupBy('tl_users.id')
            ->select([
                'tl_users.id',
                DB::raw('GROUP_CONCAT(DISTINCT tl_users.email) as customer_email'),
                DB::raw('GROUP_CONCAT(DISTINCT tl_users.name) as customer_name'),
                DB::raw('COUNT(tl_saas_accounts.id) as total_store')
            ])
            ->paginate(10);
        return view('plugin/saas::subscriber.customers.index', compact('customers'));
    }

    /**
     * Will redirect to customer profile editing page
     */
    public function editCustomerProfile($id)
    {
        $match_case = [
            ['tl_users.id', '=', $id]
        ];
        $data = [
            'tl_users.*',
            'tl_uploaded_files.path as pro_pic',
            'tl_uploaded_files.alt as pro_pic_alt',
            'tl_uploaded_files.id as pro_pic_id'
        ];
        $user = $this->user_repository->getUserProfileInfo($data, $match_case)->first();
        return view('plugin/saas::subscriber.customers.edit', compact('user'));
    }

    /**
     * Will update customer profile
     */
    public function updateCustomerProfile(Request $request)
    {
        try {
            DB::beginTransaction();
            $user = User::find($request['id']);

            if (!empty($request['password']) || !empty($request['password_confirmation'])) {
                $validator = Validator::make($request->all(), [
                    'password' => 'required|min:6|confirmed',
                ]);

                if ($validator->fails()) {
                    toastNotification('error', translate('Password confirmation does not match'));
                    return back();
                }

                $user->password = Hash::make($request['password']);
            }

            $user->name  = xss_clean($request['name']);
            $user->email = xss_clean($request['email']);
            $user->image = $request['pro_pic'];
            $user->status = isset($request['status']) ? config('settings.user_status.active') : config('settings.user_status.in_active');
            $user->update();
            DB::commit();
            toastNotification('success', translate('Customer profile updated successfully'));
            return back();
        } catch (Exception $e) {
            DB::rollBack();
            toastNotification('error', translate('Unable to update customer profile'));
            return back();
        }
    }

    /**
     * will redirect to customer details page
     */
    public function customerDetails($id)
    {
        $stores = $this->getStoreListForCustomerDetails($id);
        $payment_history = $this->getPaymentHistoryForCustomerDetails($id);
        $domain_request = CustomDomain::whereHas('saasAccount', function ($query) use ($id) {
            $query->where('user_id', $id);
        })->orderBy('id', 'desc')->get();

        return view('plugin/saas::subscriber.customers.details', compact('stores', 'payment_history', 'domain_request'));
    }

    /**
     * Will delete customer info
     */
    public function customerDelete(Request $request)
    {
        try {
            $user = User::find($request['customer_id']);
            $stores = SaasAccount::where('user_id', $user->id)->get();
            foreach ($stores as $store) {
                $tenant_id = $store->tenant_id;
                if ($store->is_db_created == 0) {
                    DB::table('tenants')->where('id', '=', $tenant_id)->delete();
                } else {
                    Tenant::find($tenant_id)->delete();
                }

                $custom_domain = DB::table('tl_saas_custom_domain')->where('store_id', '=', $store->id);
                if (!empty($custom_domain->exists())) {
                    $custom_domain_data = $custom_domain->first();
                    $status = $custom_domain_data->status;
                    if ($status == 1) {
                        DB::table('domains')->where('tenant_id', '=', $tenant_id)->update([
                            'domain' => DB::raw('main_domain')
                        ]);
                    }
                    $custom_domain->delete();
                }

                if (File::exists(public_path("/tenant/tenant$tenant_id"))) {
                    chmod(public_path("tenant/tenant$tenant_id"), 0777);
                    rrmdir(public_path("tenant/tenant$tenant_id"));
                }
            }
            $user->delete();

            toastNotification('success', translate('Successfully deleted store!'));
            return back();
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to delete store!'));
            return back();
        }
    }

    /**
     * will return store list
     */
    public function getStoreListForCustomerDetails($customer_id)
    {
        $match_case = [
            ['tl_saas_accounts.user_id', '=', $customer_id]
        ];
        $data = [
            'tl_saas_accounts.id',
            'tl_saas_accounts.status',
            'tl_saas_accounts.package_id',
            'tl_saas_accounts.package_plan as plan_id',
            'tl_saas_accounts.membership_type',
            'tl_saas_accounts.store_name',
            'tl_saas_accounts.valid_till',
            'tl_saas_accounts.tenant_id',

            'tl_saas_packages.name as package',
            'tl_saas_package_plans.name as plan',
            DB::raw('2 as subscription_status')
        ];
        $stores = $this->sub_repo->getSaasAccountDetails($match_case, $data)->get();

        $notify_before_expired_days = \Plugin\Saas\Repositories\SettingsRepository::getSaasSetting('notify_before_expired_days');

        foreach ($stores as $store) {
            if ($store->valid_till != null) {
                $current_date = new DateTime();  // Current date and time
                $valid_till = new DateTime($store->valid_till);

                $interval = $current_date->diff($valid_till);
                $diffInDays = $interval->days;

                if ($diffInDays <= $notify_before_expired_days) {
                    $store->subscription_status = 1;
                }

                if ($diffInDays <= 0) {
                    $store->subscription_status = 0;
                }
            }
            $domain = DB::table('domains')->where('tenant_id', '=', $store->tenant_id)->first();
            $store->domain = $domain != null ? $domain->domain : null;
        }

        return $stores;
    }

    /**
     * will return store list
     */
    public function getPaymentHistoryForCustomerDetails($customer_id)
    {
        $data = [
            'title',
            'method',
            'coupon_code',
            'currency',
            'discount_amount',
            'final_amount',
            'updated_at',
            'pid',
            'saas_account_id as store_id'
        ];
        $match_case = [
            ['user_id', '=', $customer_id]
        ];
        $payment_history = PaymentRepository::getPaymentHistories($data, $match_case);

        return $payment_history;
    }

    /**
     * Will assign store to customer
     */
    public function assignStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:tl_users,id',
            'store_name' => 'required|unique:tl_saas_accounts,store_name',
            'package_id' => 'required|exists:tl_saas_packages,id'
        ]);


        if ($validator->fails()) {
            toastNotification('error', translate('Please give all the information!'));
            return redirect()->back();
        }

        if (!isValidStoreName($request['store_name'])) {
            toastNotification('error', translate('Invalid/Duplicate store name!'));
            return back();
        }

        try {
            DB::beginTransaction();
            $package = Package::find((int)$request['package_id']);

            if ($package->type == 'paid') {
                $validator = Validator::make($request->all(), [
                    'plan_id' => 'required|exists:tl_saas_package_plans,id'
                ]);
                if ($validator->fails()) {
                    toastNotification('error', translate('Please select a plan!'));
                    return redirect()->back();
                }
            }


            $plan = DB::table('tl_saas_package_plans')
                ->where('id', '=', $request['plan_id'])
                ->first();

            $currentDate = Carbon::now();
            $user = User::find((int)$request['customer_id']);
            $package_details = [
                'package_id' => $package->id,
                'plan_id' => $package->type == 'paid' ? $request['plan_id'] : null,
                'membership_type' => 'member',
                'valid_till' => $package->type == 'paid' ? $currentDate->addDays((int)$plan->duration)->format("Y-m-d") : null,
                'store_name' => $request['store_name']
            ];

            $tenant = $this->sub_repo->createNewTenantAndDatabase($request);
            $saas_account = $this->sub_repo->storeSaasAccountDetails($package_details, $user, $tenant);
            DB::commit();

            $this->cp_repo->updateSingleTenantDatabase($tenant->id, $package->id, $saas_account->id, 0);

            newSubscription($saas_account->id);
            newSubscriptionNotificationAdmin($saas_account->id);
            toastNotification('success', translate('Store created successfully'));
            return back();
        } catch (Exception $ex) {
            $error = [
                'message' => 'Error occured during assign store to customer',
                'data' => request()->all(),
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            DB::rollBack();
            toastNotification('error', translate('Unable to create store!'));
            return back();
        }
    }

    /**
     * Will update store plan
     */
    public function updateStorePlan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:tl_saas_accounts,id',
            'package_id' => 'required|exists:tl_saas_packages,id'
        ]);


        if ($validator->fails()) {
            toastNotification('error', translate('Please give all valid information!'));
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $package = Package::find((int)$request['package_id']);

            if ($package->type == 'paid') {
                $validator = Validator::make($request->all(), [
                    'plan_id' => 'required|exists:tl_saas_package_plans,id'
                ]);
                if ($validator->fails()) {
                    toastNotification('error', translate('Please select a plan!'));
                    return redirect()->back();
                }

                $request['membership_type'] = 'member';
                $is_already_subscribed_with_same_plan = $this->sub_repo->isUserAlreadySubscribed($request);
                if ($is_already_subscribed_with_same_plan) {
                    toastNotification('error', translate('This plan is already assigned to this store !'));
                    return back();
                }

                $plan = DB::table('tl_saas_package_plans')
                    ->where('id', '=', $request['plan_id'])
                    ->first();
            }



            $currentDate = Carbon::now();
            $user = User::find((int)$request['customer_id']);
            $package_details = [
                'package_id' => $package->id,
                'plan_id' => $package->type == 'paid' ? $request['plan_id'] : null,
                'membership_type' => 'member',
                'valid_till' => $package->type == 'paid' ? $currentDate->addDays((int)$plan->duration)->format("Y-m-d") : null,
                'store_name' => $request['store_name']
            ];

            $saas_account = $this->sub_repo->storeSaasAccountDetails($package_details, $user, null, $request['store_id'], 1);
            DB::commit();

            $this->cp_repo->updateSingleTenantDatabase($saas_account->tenant_id, $package->id, $saas_account->id, 1);

            changeSubscription($saas_account->id);
            changeSubscriptionNotificationAdmin($saas_account->id);
            toastNotification('success', translate('Store plan updated successfully'));
            return back();
        } catch (Exception $ex) {
            $error = [
                'message' => 'Error occured during upgrading subscription plan from admin panel',
                'data' => request()->all(),
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            DB::rollBack();
            toastNotification('error', translate('Unable update store plan!'));
            return back();
        }
    }
}
