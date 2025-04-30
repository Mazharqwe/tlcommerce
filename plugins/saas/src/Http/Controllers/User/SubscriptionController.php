<?php

namespace Plugin\Saas\Http\Controllers\User;


use Exception;
use Core\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Plugin\Saas\Models\Package;
use Illuminate\Support\Facades\DB;
use Plugin\Saas\Models\PackagePlan;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Plugin\Saas\Models\PaymentMethods;
use Illuminate\Support\Facades\Validator;
use Plugin\Saas\Repositories\SubscriptionRepository;
use Plugin\Saas\Repositories\CouponPackageRepository;
use Plugin\Saas\Repositories\PaymentMethodRepository;
use Plugin\Saas\Http\Resources\PaymentMethodCollection;

class SubscriptionController extends Controller
{
    protected $payment_method_repository;
    protected $coupon_package_repo;
    protected $subscription_repository;

    public function __construct(
        PaymentMethodRepository $payment_method_repository,
        CouponPackageRepository $coupon_package_repo,
        SubscriptionRepository $subscription_repository
    ) {
        $this->payment_method_repository = $payment_method_repository;
        $this->coupon_package_repo = $coupon_package_repo;
        $this->subscription_repository = $subscription_repository;
    }

    /**
     * Will redirect to subscription form
     */
    public function subscribeNow()
    {
        try {
            $data = [
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.id)) as id'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.name)) as name'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.duration)) as duration')
            ];

            $package_plans = $this->coupon_package_repo->getPackagePlans([], $data);
            $package_plans = $package_plans->toArray();
            usort($package_plans, function ($a, $b) {
                return $a->duration - $b->duration;
            });

            $saas_account = DB::table('tl_saas_accounts')
                ->where('user_id', '=', Auth::user()->id);

            $first_plan = -1;
            if ($saas_account->exists() && $saas_account->first()->package_plan != null) {
                $first_plan = $saas_account->first()->package_plan;
            } elseif (sizeof($package_plans) > 0) {
                $first_plan = $package_plans[0]->id;
            }

            return view('plugin/saas::user.panel.subscription.subscribe', compact('package_plans', 'first_plan'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch package info !'));
            return back();
        }
    }

    /**
     * create subscription
     */
    public function confirmSubscription(Request $request)
    {
        try {
            DB::beginTransaction();
            $package = Package::find($request['package_id']);
            if ($request['is_for_update'] == 1) {
                session()->put('is_for_update', true);
                $is_already_subscribed_with_same_plan = $this->subscription_repository->isUserAlreadySubscribed($request);
                if ($is_already_subscribed_with_same_plan) {
                    toastNotification('error', translate('You are already using this plan !'));
                    return back();
                }
            }

            if ($package != null) {
                $tenant = null;
                if ($package->type == 'free') {
                    //create tenant for new subscription
                    if ($request['is_for_update'] == 0) {
                        if (!isValidStoreName($request['store_name'])) {
                            toastNotification('error', translate('Invalid/Duplicate Store Name!'));
                            return back();
                        }
                        $tenant = $this->subscription_repository->createNewTenantAndDatabase($request);
                    }

                    $total_free_store = DB::table('tl_saas_accounts')->where('package_plan', '=', null)->count();
                    if ($total_free_store >= \Plugin\Saas\Repositories\SettingsRepository::getSaasSetting('maximum_free_store')) {
                        toastNotification('error', translate('You cannot create another free store as free store creation quota is over !'));
                        return back();
                    }

                    //create/update saas account
                    $saas_account_id = $this->subscription_repository->createSaasAccountForFreePackage($request, $package, $tenant, $request['is_for_update'], $request['store_id']);

                    $user = User::find(Auth::user()->id);

                    //Sending Notification
                    if ($request['is_for_update'] == 0) {
                        newSubscription($saas_account_id);
                        newSubscriptionNotificationAdmin($saas_account_id);
                    } else {
                        changeSubscription($saas_account_id);
                        changeSubscriptionNotificationAdmin($saas_account_id);
                    }

                    DB::commit();
                    if ($request['is_for_update'] == 0) {
                        $this->coupon_package_repo->updateSingleTenantDatabase($tenant->id, $package->id, $saas_account_id, $request['is_for_update']);
                    } else {
                        $this->coupon_package_repo->updateSingleTenantDatabase($tenant, $package->id, $saas_account_id, $request['is_for_update']);
                    }

                    toastNotification('success', translate('You have successfully subscribed to ' . $package->name . ' !'));
                    return redirect()->route('plugin.saas.user.dashboard');
                } else {
                    $billing_details = DB::table('tl_saas_payment_histories')
                        ->where('user_id', '=', Auth::user()->id)
                        ->first();

                    $plan = PackagePlan::find((int)$request['plan_id']);
                    $package_with_plans = DB::table('tl_saas_package_has_plans')
                        ->where('package_id', '=', $request['package_id'])
                        ->where('plan_id', '=', $request['plan_id'])
                        ->first();

                    $amount = $package_with_plans->cost;
                    $payment_gateways =  new PaymentMethodCollection($this->payment_method_repository->paymentMethods(config('settings.general_status.active')));

                    foreach ($payment_gateways as $gateway) {
                        $logo = \Plugin\Saas\Repositories\PaymentMethodRepository::configKeyValue(config('saas.payment_methods.' . strtolower($gateway->name)), strtolower($gateway->name) . '_logo');

                        if (!empty($logo)) {
                            $gateway->logo = asset(getFilePath($logo));
                        }
                    }

                    $data_to_confirm = [
                        'package_id' => $package->id,
                        'package_name' => $package->name,
                        'plan_id' => $plan->id,
                        'package_plan' => $plan->name,
                        'primary_amount' => $amount,
                        'store_id' => $request['store_id'],
                    ];

                    DB::commit();
                    return view('plugin/saas::user.panel.subscription.confirm', compact('data_to_confirm', 'payment_gateways', 'billing_details'));
                }
            }
        } catch (Exception $ex) {
            if ($request['is_for_update'] == 0) {
                $error = [
                    'message' => 'Error occured during creating new store',
                    'data' => request()->all(),
                    'error' => $ex
                ];
                Log::channel('tenant_database')->info(json_encode($error));
            } else {
                $error = [
                    'message' => 'Error occured during renew/upgrade/downgrade subscription plan',
                    'data' => request()->all(),
                    'error' => $ex
                ];
                Log::channel('tenant_database')->info(json_encode($error));
            }


            toastNotification('error', translate('Unable to subscribe!'));
            DB::rollBack();
            return back();
        }
    }

    /**
     * Will return states of selected country
     */
    public function getStatesOfCountry(Request $request)
    {
        try {
            $all_states = getStatesByCountryId($request['country_id'])->get();

            return response()->json([
                'success' => true,
                'states' => $all_states,
                'message' => translate('Data retrieved successfully')
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => translate('Data retrieved unsuccessful')
            ]);
        }
    }

    /**
     * Will return cities of selected state
     */
    public function getCitiesOfState(Request $request)
    {
        try {
            $all_cities = getCitiesByStateId($request['state_id'])->get();

            return response()->json([
                'success' => true,
                'cities' => $all_cities,
                'message' => translate('Data retrieved successfully')
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => translate('Data retrieved unsuccessful')
            ]);
        }
    }

    /**
     * Apply coupon
     */
    public function applyCoupon(Request $request)
    {
        try {
            $currentDate = date('Y-m-d');
            $coupon = DB::table('tl_saas_coupons')
                ->join('tl_saas_coupons_of_packages', 'tl_saas_coupons_of_packages.coupon_id', '=', 'tl_saas_coupons.id')
                ->where('tl_saas_coupons.coupon_code', '=', $request['coupon'])
                ->where('tl_saas_coupons_of_packages.package_id', '=', $request['package'])
                ->where('valid_till', '>=', $currentDate)
                ->select([
                    'discount',
                    'total_used',
                    'coupon_usable_times'
                ])->first();

            if (!empty($coupon) && $coupon->total_used < $coupon->coupon_usable_times) {
                $discount = (float)$coupon->discount;

                $package_price = (float)DB::table('tl_saas_package_has_plans')
                    ->where('package_id', '=', $request['package'])
                    ->where('plan_id', '=', $request['plan_id'])
                    ->first()->cost;

                $discount_amount = ($discount * $package_price) / 100;
                $total = (float)$package_price - $discount_amount;

                $summery = [
                    'discount_amount' => currencyExchange($discount_amount),
                    'discount_amount_value' => $discount_amount,
                    'total' => currencyExchange($total),
                    'total_value' => $total,
                ];

                return response()->json([
                    'success' => true,
                    'summery' => $summery,
                    'message' => translate('Price after applying coupon')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => translate('This coupon is not applicable here')
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => translate('This coupon is not applicable here')
            ]);
        }
    }

    /**
     * Make payment
     */
    public function makePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'country' => 'nullable|exists:tl_countries,id',
            'state' => 'nullable|exists:tl_states,id',
            'city' => 'nullable|exists:tl_cities,id',
            'address' => 'required',
            'payment_method' => 'required|exists:tl_saas_payment_methods,id'
        ]);

        if (!$validator->fails() && $request['store_id'] == "null") {
            $validator = Validator::make($request->all(), [
                'store_name' => 'required|unique:tl_saas_accounts,store_name',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $saas_account_id = $request['store_id'];
            $tenant = null;
            $subscription_repository = new SubscriptionRepository();

            if ($saas_account_id == "null") {
                $tenant = $subscription_repository->createNewTenantAndDatabase($request);
            }


            $payment_method = PaymentMethods::find($request['payment_method']);
            $base_url = url('/');
            $url = $base_url . '/user/' . getSaasPrefix() . '/payment/' . Str::slug($payment_method->name) . '/pay';


            session()->put('payment_type', 'checkout');
            session()->put('payable_amount', $request['amount']);
            session()->put('payment_method', $payment_method->name);
            session()->put('payment_method_id', $payment_method->id);
            session()->put('redirect_url', $url);

            session()->put('name', $request['name']);
            session()->put('email', $request['email']);
            session()->put('phone', $request['phone']);
            session()->put('country', isset($request['country']) ? $request['country'] : null);
            session()->put('state', isset($request['state']) ? $request['state'] : null);
            session()->put('city', isset($request['city']) ? $request['city'] : null);
            session()->put('address', $request['address']);
            session()->put('coupon_code', $request['coupon_code']);
            session()->put('primary_amount', $request['primary_amount']);
            session()->put('discount_amount', $request['discount_amount']);
            session()->put('package_id', $request['package_id']);
            session()->put('plan_id', $request['plan_id']);
            session()->put('currency', getSaasCurrency());
            session()->put('store_id', $saas_account_id);
            session()->put('store_name', $request['store_name']);
            session()->put('tenant', $tenant);
            session()->put('is_for_update', $saas_account_id == "null" ? false : true);

            DB::commit();

            return response()->json(
                [
                    'success' => true,
                    'response_url' => $url
                ]
            );
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json(
                [
                    'success' => false,
                ]
            );
        }
    }

    /**
     * redirect to redeem coupon page
     */
    public function redeemCoupon()
    {
        return view('plugin/saas::user.panel.subscription.redeem_coupon');
    }

    /**
     * Apply redeem coupon
     */
    public function applyRedeemCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|exists:tl_saas_coupons,coupon_code',
            'store_name' => 'required|unique:tl_saas_accounts,store_name',
        ]);

        if ($validator->fails()) {
            toastNotification('error', translate('Invalid coupon!'));
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if (!isValidStoreName($request['store_name'])) {
            toastNotification('error', translate('Invalid/Duplicate store name!'));
            return back();
        }

        try {
            $currentDate = date('Y-m-d');
            $coupon = DB::table('tl_saas_coupons')
                ->where('coupon_code', '=', $request['coupon_code'])
                ->where('coupon_type', '=', 'redeemable')
                ->where('valid_till', '>=', $currentDate)
                ->select([
                    'id',
                    'total_used',
                    'coupon_usable_times',
                    'coupon_code'
                ])->first();

            if (empty($coupon) || $coupon->total_used >= $coupon->coupon_usable_times) {
                toastNotification('error', translate('Invalid coupon!'));
                return back();
            }

            $package_id = getPackageByCouponIdAndType($coupon->id, 'redeemable');

            $package = Package::find((int)$package_id);

            $user = Auth::user();
            $package_details = [
                'package_id' => $package_id,
                'plan_id' => config('saas.plans.lifetime'),
                'membership_type' => 'member',
                'valid_till' => null,
                'store_name' => $request['store_name']
            ];

            DB::beginTransaction();
            $tenant = $this->subscription_repository->createNewTenantAndDatabase($request);
            $saas_account = $this->subscription_repository->storeSaasAccountDetails($package_details, $user, $tenant);


            session()->put('tenant_id', $tenant->id);
            session()->put('user_id', $user->id);

            $request = [
                'package_id' => $package_id,
                'plan_id' => config('saas.plans.lifetime'),
                'membership_type' => 'member',
            ];

            updateCouponInfo($coupon->coupon_code);
            DB::commit();

            $this->coupon_package_repo->updateSingleTenantDatabase($saas_account->tenant_id, $package->id, $saas_account->id, 0);


            newSubscription($saas_account->id);
            newSubscriptionNotificationAdmin($saas_account->id);

            toastNotification('success', translate('You have successfully subscribed to ' . $package->name . ' for lifetime !'));
            return redirect()->route('plugin.saas.user.dashboard');
        } catch (Exception $ex) {
            $error = [
                'message' => 'Error occured during redeem coupon to create new store',
                'data' => request()->all(),
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            DB::rollBack();
            toastNotification('error', translate('Redeem unsuccessful!'));
            return back();
        }
    }

    /**
     * Fetch payment history
     */
    public function paymentHistory()
    {
        try {
            $payment_history = DB::table('tl_saas_payment_histories')
                ->where('user_id', '=', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->select([
                    'title',
                    'method',
                    'coupon_code',
                    'currency',
                    'discount_amount',
                    'final_amount',
                    'updated_at',
                    'pid',
                    'saas_account_id as store_id'
                ])->get();

            return view('plugin/saas::user.panel.subscription.payment_history', compact('payment_history'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch payment history!'));
            return back();
        }
    }

    /**
     * Will redirect to subscription plan changing page
     */
    public function changeSubscriptionPlan($store_id)
    {
        try {
            $data = [
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.id)) as id'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.name)) as name'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.duration)) as duration')
            ];

            $package_plans = $this->coupon_package_repo->getPackagePlans([], $data);

            $saas_account = DB::table('tl_saas_accounts')
                ->where('id', '=', $store_id);

            $first_plan = -1;
            if ($saas_account->exists() && $saas_account->first()->package_plan != null) {
                $first_plan = $saas_account->first()->package_plan;
            } elseif (sizeof($package_plans) > 0) {
                $first_plan = $package_plans[0]->id;
            }

            return view('plugin/saas::user.panel.subscription.subscribe', compact('package_plans', 'first_plan', 'store_id'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch package info !'));
            return back();
        }
    }
}
