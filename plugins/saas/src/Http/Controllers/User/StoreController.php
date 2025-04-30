<?php

namespace Plugin\Saas\Http\Controllers\User;

use PDF;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Plugin\Saas\Models\Currency;
use Illuminate\Support\Facades\DB;
use Plugin\Saas\Models\SaasAccount;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Plugin\Saas\Repositories\SettingsRepository;
use Plugin\Saas\Repositories\SubscriptionRepository;
use Plugin\Saas\Repositories\CouponPackageRepository;

class StoreController extends Controller
{
    public $sub_repo;
    public $coupon_package_repo;

    public function __construct(SubscriptionRepository $sub_repo, CouponPackageRepository $coupon_package_repo)
    {
        $this->sub_repo = $sub_repo;
        $this->coupon_package_repo = $coupon_package_repo;
    }

    /**
     * Will show all stores of user
     */
    public function index()
    {
        $match_case = [
            ['tl_saas_accounts.user_id', '=', Auth::user()->id]
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

        return view('plugin/saas::user.panel.store.index', compact('stores'));
    }

    /**
     * Will return store details
     */
    public function storeDetails($store_id)
    {
        $data = [
            'tl_users.name as subscriber',
            'tl_saas_packages.id as package_id',
            'tl_saas_packages.name as package_name',
            'tl_saas_packages.type as package_type',
            'tl_saas_package_plans.name as plan_name',
            'tl_saas_accounts.membership_type',
            'tl_saas_accounts.created_at as created_at',
            'tl_saas_accounts.valid_till as due_date',
            'tl_saas_accounts.store_name',
            'tl_saas_accounts.status',
            'tl_saas_accounts.tenant_id',
            'tl_saas_accounts.package_plan',
            'tl_saas_accounts.id as store_id',
            'tl_saas_accounts.renewed'
        ];

        $match_case = [
            ['tl_saas_accounts.id', '=', $store_id]
        ];

        $saas_account_details = $this->sub_repo->getSaasAccountDetails($match_case, $data)->first();

        if ($saas_account_details != null) {
            $domain = DB::table('domains')->where('tenant_id', '=', $saas_account_details->tenant_id)->first();
            $saas_account_details->domain = $domain != null ? $domain->domain : null;

            $package_details = $this->getPackageDetailsForSaasAccount($saas_account_details);
            $payment_history = $this->getPaymentHistoryBySaasAccount($store_id)->get();

            return view('plugin/saas::user.panel.store.storeDetails', compact('saas_account_details', 'package_details', 'payment_history'));
        } else {
            abort(404);
        }
    }

    /**
     * Will return package details for saas account
     */
    public function getPackageDetailsForSaasAccount($saas_account_details)
    {
        $match_case = [
            ['tl_saas_packages.id', '=', $saas_account_details->package_id],
        ];

        if ($saas_account_details->package_type == 'paid') {
            array_push($match_case, [
                'tl_saas_package_plans.id', '=', $saas_account_details->package_plan
            ]);
        } else {
            array_push($match_case, ['tl_saas_packages.type', '=', 'free']);
        }

        $data = [
            DB::raw('"" as plugins'),
            DB::raw('"" as privileges'),
            DB::raw('"" as payment_methods'),
        ];
        $package_details = $this->coupon_package_repo->getPackagesByPlan($match_case, $data);

        for ($i = 0; $i < sizeof($package_details); $i++) {
            $package = $package_details[$i];
            foreach ($package as $key => $value) {
                if ($key == 'plugins') {
                    $package->$key = $this->coupon_package_repo->getPluginsOfPackage($saas_account_details->package_id);
                }
                if ($key == 'privileges') {
                    $package->$key = json_decode($this->coupon_package_repo->getPrivilegesOfPackage($saas_account_details->package_id));
                }
                if ($key == 'payment_methods') {
                    $package->$key = $this->coupon_package_repo->getPaymentMethodsOfPackage($saas_account_details->package_id);
                }
            }
        }

        $package_details = $package_details[0];

        return $package_details;
    }

    /**
     * Will return payment history by saas account
     */
    public function getPaymentHistoryBySaasAccount($saas_account_id)
    {
        $payment_history = DB::table('tl_saas_payment_histories')
            ->where('user_id', '=', Auth::user()->id)
            ->where('tl_saas_payment_histories.saas_account_id', '=', $saas_account_id)
            ->orderBy('id', 'desc')
            ->select([
                'tl_saas_payment_histories.id',
                'tl_saas_payment_histories.title',
                'tl_saas_payment_histories.method',
                'tl_saas_payment_histories.coupon_code',
                'tl_saas_payment_histories.currency',
                'tl_saas_payment_histories.discount_amount',
                'tl_saas_payment_histories.primary_amount',
                'tl_saas_payment_histories.discount_amount',
                'tl_saas_payment_histories.final_amount',
                'tl_saas_payment_histories.updated_at',
                'tl_saas_payment_histories.pid'
            ]);

        return $payment_history;
    }

    /**
     * Will print invoice
     */
    public function printInvoice($store_id)
    {
        $admin_logo = DB::table('tl_general_settings')
            ->join('tl_general_settings_has_values', 'tl_general_settings_has_values.settings_id', '=', 'tl_general_settings.id')
            ->where('tl_general_settings.name', '=', 'admin_logo')
            ->value('value');

        $site_title = DB::table('tl_general_settings')
            ->join('tl_general_settings_has_values', 'tl_general_settings_has_values.settings_id', '=', 'tl_general_settings.id')
            ->where('tl_general_settings.name', '=', 'system_name')
            ->value('value');

        $payment_history = $this->getPaymentHistoryBySaasAccount($store_id)->first();

        $font_family = "Roboto";
        $local = getLocale();

        if ($local  == 'bd') {
            $font_family = 'Bangla';
        }

        if ($local  == 'sa') {
            $font_family = 'Arabic';
        }

        if ($local  == 'il') {
            $font_family = 'Hebrew';
        }

        $default_currency_id = $default_currency_id = SettingsRepository::getSaasSetting('default_currency');
        $default_currency = Currency::find($default_currency_id);
        $currency_font = 'Arial Unicode MS';
        if ($default_currency->symbol == '₹') {
            $currency_font = 'Roboto';
        }

        $data = [
            'admin_logo' => $admin_logo,
            'site_title' => $site_title,
            'payment_history' => $payment_history,
            'font_family' => $font_family,
            'currency_font' => $currency_font,
        ];

        $default_language = getLocale();
        $is_rtl = DB::table('tl_languages')
            ->where('code', '=', $default_language)
            ->where('is_rtl', '=', 1)
            ->exists();
        if ($is_rtl) {
            $invoice_view = 'plugin/saas::user.panel.subscription.invoice_rtl';
            $pdf = PDF::loadView($invoice_view, $data)->set_option('isFontSubSettingEnabled', true);
        } else {
            $invoice_view = 'plugin/saas::user.panel.subscription.invoice';
            $pdf = PDF::loadView($invoice_view, $data)->set_option('isFontSubSettingEnabled', true);
        }

        return $pdf->download();
    }

    /**
     * Update store
     */
    public function updateStore(Request $request)
    {
        if (!isValidStoreName($request['store_name'])) {
            toastNotification('error', translate('Your given store name is already in use!'));
            return back();
        }

        try {
            $store = SaasAccount::find($request['store_id']);
            $store->store_name = $request['store_name'];
            $store->update();
            toastNotification('success', translate('Store updated Successfully!'));
            return back();
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to update store!'));
            return back();
        }
    }
}
