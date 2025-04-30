<?php

namespace Plugin\Saas\Http\Controllers\User;

use DateTime;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Plugin\Saas\Models\CustomDomain;
use Plugin\Saas\Repositories\SubscriptionRepository;

class DashboardController extends Controller
{
    public $sub_repo;

    public function __construct(SubscriptionRepository $sub_repo)
    {
        $this->sub_repo = $sub_repo;
    }

    /*
     * will return saas dashboard
     */
    public function saasDashboard()
    {
        $stores = $this->getStoreListForDashboard();
        $payment_history = $this->getPaymentHistoryForDashboard();
        $domain_request = CustomDomain::whereHas('saasAccount', function ($query) {
            $query->where('user_id', Auth::user()->id);
        })->orderBy('id', 'desc')->take(5)->get();
        return view('plugin/saas::user.panel.dashboard', compact('stores', 'payment_history','domain_request'));
    }

    /**
     * will return store list for dashboard
     */
    public function getStoreListForDashboard()
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
        $stores = $this->sub_repo->getSaasAccountDetails($match_case, $data)->take(5)->get();

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
     * will return payment history for dashboard
     */
    public function getPaymentHistoryForDashboard()
    {
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
            ])->take(5)->get();

        return $payment_history;
    }
}
