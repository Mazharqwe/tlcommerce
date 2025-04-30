<?php

use App\Models\User as ModelsUser;
use Carbon\Carbon;
use Core\Models\User;
use Plugin\Saas\Models\Package;
use Plugin\Saas\Models\Currency;
use Illuminate\Support\Facades\DB;
use Plugin\Saas\Models\PackagePlan;
use Plugin\Saas\Models\SaasAccount;
use Illuminate\Support\Facades\Mail;
use Plugin\Saas\Models\CustomDomain;
use Illuminate\Support\Facades\Cache;
use Plugin\Saas\Mail\CommonSaasEmail;
use Plugin\Saas\Models\PaymentHistory;
use Illuminate\Support\Facades\Request;
use Plugin\Saas\Models\TicketCategory;
use Plugin\Saas\Notifications\UserNotification;
use Plugin\Saas\Repositories\SettingsRepository;
use Plugin\Saas\Repositories\SubscriptionRepository;
use Plugin\Saas\Repositories\CouponPackageRepository;

if (!function_exists('getAllPaidPackages')) {
    /**
     * get all active paid packages
     * @return mixed|array
     */
    function getAllPaidPackages()
    {
        $all_packages = DB::table('tl_saas_packages')
            ->where('tl_saas_packages.type', '=', 'paid')
            ->where('tl_saas_packages.status', '=', config('settings.general_status.active'))
            ->select([
                'tl_saas_packages.name',
                'tl_saas_packages.id'
            ])->get();

        return $all_packages;
    }
}

if (!function_exists('getPackagePlans')) {
    /**
     * get all package plans
     * @return mixed|array
     */
    function getPackagePlans()
    {
        $data = [
            DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.id)) as id'),
            DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.name)) as name'),
            DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.duration)) as duration')
        ];
        $coupon_package_repo = new CouponPackageRepository();
        $package_plans = $coupon_package_repo->getPackagePlans([], $data);

        $package_plans = $package_plans->toArray();
        usort($package_plans, function ($a, $b) {
            return $a->duration - $b->duration;
        });

        return $package_plans;
    }
}

if (!function_exists('getAllActivePackages')) {
    /**
     * get all active packages
     * @return mixed|array
     */
    function getAllActivePackages()
    {
        $all_packages = DB::table('tl_saas_packages')
            ->where('tl_saas_packages.status', '=', config('settings.general_status.active'))
            ->select([
                'tl_saas_packages.name',
                'tl_saas_packages.id'
            ])->get();

        return $all_packages;
    }
}

if (!function_exists('getAllPackagesByCouponId')) {
    /**
     * get all packages by coupon id
     * @return mixed|array
     */
    function getAllPackagesByCouponId($coupon_id)
    {
        $all_packages = DB::table('tl_saas_coupons')
            ->join('tl_saas_coupons_of_packages', 'tl_saas_coupons_of_packages.coupon_id', '=', 'tl_saas_coupons.id')
            ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_coupons_of_packages.package_id')
            ->where('tl_saas_coupons.id', '=', $coupon_id)
            ->pluck('tl_saas_packages.name as package');
        return $all_packages;
    }
}

if (!function_exists('getPackageByCouponIdAndType')) {
    /**
     * get package by coupon id & type
     * @return mixed|array
     */
    function getPackageByCouponIdAndType($coupon_id, $coupon_type)
    {
        $package = DB::table('tl_saas_coupons')
            ->join('tl_saas_coupons_of_packages', 'tl_saas_coupons_of_packages.coupon_id', '=', 'tl_saas_coupons.id')
            ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_coupons_of_packages.package_id')
            ->where('tl_saas_coupons.id', '=', $coupon_id);
        if ($coupon_type == 'discount') {
            $package = $package->pluck('tl_saas_packages.name as package');
        } else {
            $package = $package->select([
                'tl_saas_packages.id'
            ])->first()->id;
        }
        return $package;
    }
}

if (!function_exists('getAllPackageIdsByCouponId')) {
    /**
     * get all packages by coupon id
     * @return mixed|array
     */
    function getAllPackageIdsByCouponId($coupon_id)
    {
        $all_packages = DB::table('tl_saas_coupons')
            ->join('tl_saas_coupons_of_packages', 'tl_saas_coupons_of_packages.coupon_id', '=', 'tl_saas_coupons.id')
            ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_coupons_of_packages.package_id')
            ->where('tl_saas_coupons.id', '=', $coupon_id)
            ->pluck('tl_saas_packages.id as package');
        return $all_packages;
    }
}

if (!function_exists('getAllPlans')) {
    /**
     * get all plans
     * @return mixed|array
     */
    function getAllPlans()
    {
        $all_package_plans = DB::table('tl_saas_package_plans')
            ->select([
                'name',
                'id'
            ])->get();
        return $all_package_plans;
    }
}

if (!function_exists('getAllPlansByPackageId')) {
    /**
     * get all package plans by package id
     * @return mixed|array
     */
    function getAllPlansByPackageId($package_id)
    {
        $all_package_plans = DB::table('tl_saas_package_plans')
            ->join('tl_saas_package_has_plans', 'tl_saas_package_has_plans.plan_id', '=', 'tl_saas_package_plans.id')
            ->groupBy('tl_saas_package_plans.id')
            ->where('tl_saas_package_has_plans.package_id', '=', $package_id)
            ->select([
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.id)) as id'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.name)) as name')
            ])->get();

        return $all_package_plans;
    }
}

if (!function_exists('getAllActivePaymentMethods')) {
    /**
     * get all active payment methods
     * @return mixed|array
     */
    function getAllActivePaymentMethods()
    {
        $all_payment_methods = DB::table('tl_saas_payment_methods')
            ->where('tl_saas_payment_methods.status', '=', config('settings.general_status.active'))
            ->select([
                'id',
                'name'
            ])->get();

        return $all_payment_methods;
    }
}

if (!function_exists('getAllCountries')) {
    /**
     * get all countries
     * @return mixed|array
     */
    function getAllCountries()
    {
        $all_countries = DB::table('tl_countries')->select([
            'id',
            'name'
        ])->get();

        return $all_countries;
    }
}


if (!function_exists('getSaasCurrency')) {
    /**
     * get default saas currency
     * @return mixed|array
     */
    function getSaasCurrency()
    {
        $saas_currency = \Plugin\Saas\Models\Currency::where('id', \Plugin\Saas\Repositories\SettingsRepository::getSaasSetting('default_currency'))
            ->select('code', 'conversion_rate')
            ->first();

        return $saas_currency;
    }
}

if (!function_exists('currencyExchange')) {
    /**
     * Exchange currency
     */
    function currencyExchange($value, $formatting = true, $target_currency_id = NULL)
    {
        //Get system currency
        $default_currency_details = Cache::rememberForever('default-saas-currency-details', function () {
            $default_currency_id = SettingsRepository::getSaasSetting('default_currency');
            return Currency::find($default_currency_id);
        });

        //Convert to target currency
        if ($target_currency_id != null) {
            $target_currency = Currency::where('id', $target_currency_id)->select('conversion_rate')->first();
            $converted_amount = ($value / $target_currency->conversion_rate) * $default_currency_details->conversion_rate;
            $value = $converted_amount;
        }

        //Formatting Currency
        if ($default_currency_details != null) {
            if ($formatting) {
                $formatting_value = number_format($value, $default_currency_details->number_of_decimal, $default_currency_details->decimal_separator, $default_currency_details->thousand_separator);
                $position = $default_currency_details->position;

                switch ($position) {
                    case "1":
                        return $default_currency_details->symbol . '' . $formatting_value;
                        break;
                    case "2":
                        return $formatting_value . '' . $default_currency_details->symbol;
                        break;
                    case "3":
                        return $default_currency_details->symbol . ' ' . $formatting_value;
                        break;
                    case "4":
                        return $formatting_value . ' ' . $default_currency_details->symbol;
                        break;
                    default:
                        return $formatting_value;
                }
            } else {
                return $value;
            }
        } else {
            return $value;
        }
    }
}

if (!function_exists('getCurrentPlan')) {
    /**
     * will return current plan
     *
     */
    function getCurrentPlan($store_id)
    {
        $current_plan = null;
        $saas_account = DB::table('tl_saas_accounts')
            ->where('id', '=', $store_id)
            ->first();
        $package_type = Package::find((int)$saas_account->package_id)->type;

        $plan_duration = null;
        if ($package_type == 'paid') {
            $plan_duration = PackagePlan::find((int)$saas_account->package_plan)->duration;
        }
        if ($saas_account) {
            $current_plan = [
                'package_id' => $saas_account->package_id,
                'package_plan' => $saas_account->package_plan,
                'plan_duration' => $plan_duration,
                'package_type' => $package_type,
            ];
        }

        return $current_plan;
    }
}


if (!function_exists('getStatesByCountryId')) {
    /**
     * will return query of states of requested country
     *
     */
    function getStatesByCountryId($country_id)
    {
        $query = DB::table('tl_countries')
            ->join('tl_states', 'tl_states.country_id', '=', 'tl_countries.id')
            ->where('tl_countries.id', '=', $country_id)
            ->select([
                'tl_states.id',
                'tl_states.name'
            ]);

        return $query;
    }
}

if (!function_exists('getCitiesByStateId')) {
    /**
     * will return query of cities of requested state
     *
     */
    function getCitiesByStateId($state_id)
    {
        $query = DB::table('tl_states')
            ->join('tl_cities', 'tl_cities.state_id', '=', 'tl_states.id')
            ->where('tl_states.id', '=', $state_id)
            ->select([
                'tl_cities.id',
                'tl_cities.name'
            ]);

        return $query;
    }
}


if (!function_exists('registrationSuccessNotificationToUser')) {
    /**
     * will notify user about successful registration notification
     *
     */
    function registrationSuccessNotificationToUser($user, $data)
    {
        $user->notify(new UserNotification($data));
    }
}

if (!function_exists('newSubscriptionNotificationToUser')) {
    /**
     * will send new subscription notification to subscriber
     *
     */
    function newSubscriptionNotificationToUser($user, $request, $is_on_registration = true)
    {
        $mail_data = [
            'template_id' => 6,
            'keywords' => getEmailTemplateVariables(6, true),
            'subject' => getSubjectByTemplateId(6),
            '_app_name_' =>  env('APP_NAME'),
            '_subscriber_login_page_' =>  url('/') . '/' . 'user/' . getSaasPrefix() . '/login'
        ];

        if ($is_on_registration) {
            $membership_type = "member";
            $package = Package::find((int)$request['package']);
            if ($package->type == 'free') {
                $mail_data['_package_name_'] = $package->name;
                $mail_data['_valid_till_'] = "for lifetime";
                $mail_data['_membership_type_'] = $membership_type;
            } else {
                $plan = PackagePlan::find((int)$request['plan']);
                $currentDate = Carbon::now();
                if (!isset($request['coupon'])) {
                    $membership_type = "trail user";
                    $valid_till = $currentDate->addDays((int)$package->trail_period)->format("Y-m-d");

                    $mail_data['_package_name_'] = $package->name;
                    $mail_data['_valid_till_'] = "till " . $valid_till;
                    $mail_data['_membership_type_'] = $membership_type;
                } else {
                    $valid_till = $currentDate->addDays((int)$plan->duration)->format("Y-m-d");

                    $mail_data['_package_name_'] = $package->name;
                    $mail_data['_valid_till_'] = "for lifetime";
                    $mail_data['_membership_type_'] = $membership_type;
                }
            }
        } else {
            $membership_type = $request['membership_type'];
            $package = Package::find((int)$request['package_id']);
            if ($package->type == 'free') {
                $mail_data['_package_name_'] = $package->name;
                $mail_data['_valid_till_'] = "for lifetime";
                $mail_data['_membership_type_'] = $membership_type;
            } else {
                $plan = PackagePlan::find((int)$request['plan_id']);
                $currentDate = Carbon::now();
                if ($membership_type == 'trail') {
                    $valid_till = $currentDate->addDays((int)$package->trail_period)->format("Y-m-d");

                    $mail_data['_package_name_'] = $package->name;
                    $mail_data['_valid_till_'] = $valid_till;
                    $mail_data['_membership_type_'] = $membership_type;
                } else {
                    $valid_till = $currentDate->addDays((int)$plan->duration)->format("Y-m-d");

                    $mail_data['_package_name_'] = $package->name;
                    $mail_data['_valid_till_'] = $valid_till;
                    $mail_data['_membership_type_'] = $membership_type;
                }
            }
        }
        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

/** Saas Customer Notification Start */
if (!function_exists('registrationSuccessfulNotification')) {
    /**
     * will send registration successful notification to subscriber
     *
     */
    function registrationSuccessfulNotification($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $mail_data = [
            'template_id' => 6,
            'keywords' => getEmailTemplateVariables(6, true),
            'subject' => getSubjectByTemplateId(6),

            '_package_name_' =>  $saas_account->package_name,
            '_app_name_' =>  env('APP_NAME'),
            '_subscriber_login_page_' =>  url('/') . '/' . 'user/' . getSaasPrefix() . '/login'
        ];

        $user = User::find($saas_account->user_id);
        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('newSubscription')) {
    /**
     * will send new subscription notification to subscriber
     *
     */
    function newSubscription($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $mail_data = [
            'template_id' => 15,
            'keywords' => getEmailTemplateVariables(15, true),
            'subject' => getSubjectByTemplateId(15),

            '_package_name_' =>  $saas_account->package_name,
            '_member_type_' =>  $saas_account->membership_type,
            '_valid_till_' =>  $saas_account->valid_till,

            '_store_name_' => $saas_account->store_name,
            '_store_link_' =>  'https://' . $saas_account->domain,
            '_contact_us_' =>  'https://' . $saas_account->domain . '/contact'
        ];

        $user = User::find($saas_account->user_id);

        $data = [
            'message' => 'Subscription successful.' . 'Store Name: ' . $saas_account->store_name . ' Plan Name: ' . $saas_account->plan_name . ' Package Name: ' . $saas_account->package_name . ' Status: ' . ($saas_account->status == 1 ? 'Active' . ' Till: ' . $saas_account->valid_till : 'Inactive'),
            'link' => '/user/' . getSaasPrefix() . '/stores'
        ];
        $user->notify(new UserNotification($data));

        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('subscriptionReminder')) {
    /**
     * will send new reminder notification before subscription expired
     *
     */
    function subscriptionReminder($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $mail_data = [
            'template_id' => 16,
            'keywords' => getEmailTemplateVariables(16, true),
            'subject' => getSubjectByTemplateId(16),
            '_valid_till_' =>  $saas_account->valid_till,
            '_store_name_' => $saas_account->store_name,
            '_subscription_renew_link_' =>  url('/') . '/' . 'user/' . getSaasPrefix() . '/change-subscription-plan/' . $saas_account->id,
            '_contact_us_' =>  'https://' . $saas_account->domain . '/contact'
        ];

        $user = User::find($saas_account->user_id);
        $data = [
            'message' => 'Your subscription will end on ' . $saas_account->valid_till . '. Please renew subscription to stay with us',
            'link' => '/user/' . getSaasPrefix() . '/stores'
        ];
        $user->notify(new UserNotification($data));
        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('subscriptionExpired')) {
    /**
     * will send new subscription expired notification
     *
     */
    function subscriptionExpired($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);
        $mail_data = [
            'template_id' => 17,
            'keywords' => getEmailTemplateVariables(17, true),
            'subject' => getSubjectByTemplateId(17),
            '_store_name_' => $saas_account->store_name,
            '_subscription_renew_link_' =>  url('/') . '/' . 'user/' . getSaasPrefix() . '/change-subscription-plan/' . $saas_account->id,
            '_contact_us_' =>  'https://' . $saas_account->domain . '/contact'
        ];

        $user = User::find($saas_account->user_id);
        $data = [
            'message' => 'Your subscription period ends . Please renew subscription to stay with us',
            'link' => '/user/' . getSaasPrefix() . '/stores'
        ];
        $user->notify(new UserNotification($data));

        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('changeSubscription')) {
    /**
     * will send new subscription notification after changing subscription plan
     */
    function changeSubscription($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $mail_data = [
            'template_id' => 18,
            'keywords' => getEmailTemplateVariables(18, true),
            'subject' => getSubjectByTemplateId(18),

            '_package_name_' =>  $saas_account->package_name,
            '_member_type_' =>  $saas_account->membership_type,
            '_valid_till_' =>  $saas_account->valid_till,

            '_store_name_' => $saas_account->store_name,
            '_store_link_' =>  'https://' . $saas_account->domain,
            '_contact_us_' =>  'https://' . $saas_account->domain . '/contact'
        ];

        $user = User::find($saas_account->user_id);
        $data = [
            'message' => 'Subscription plan updated.' . 'Store Name: ' . $saas_account->store_name . ' Plan Name: ' . $saas_account->plan_name . ' Package Name: ' . $saas_account->package_name . ' Status: ' . ($saas_account->status == 1 ? 'Active' . ' Till: ' . $saas_account->valid_till : 'Inactive'),
            'link' => '/user/' . getSaasPrefix() . '/stores'
        ];
        $user->notify(new UserNotification($data));


        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('storeStatusUpdate')) {
    /**
     * will send store status update notification
     *
     */
    function storeStatusUpdate($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $mail_data = [
            'template_id' => 23,
            'keywords' => getEmailTemplateVariables(23, true),
            'subject' => getSubjectByTemplateId(23),

            '_store_name_' => $saas_account->store_name,
            '_status_' => $saas_account->store_name,
            '_store_list_page_' => url('/') . '/' . 'user/' . getSaasPrefix() . '/stores'
        ];

        $user = User::find($saas_account->user_id);
        $data = [
            'message' => 'Store status updated.' . 'Store Name: ' . $saas_account->store_name . ' Status: ' . ($saas_account->status == 1 ? 'Active' : 'Inactive'),
            'link' => '/user/' . getSaasPrefix() . '/stores'
        ];
        $user->notify(new UserNotification($data));

        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}

if (!function_exists('customDomainRequestStatusUpdate')) {
    /**
     * will send custom domain request status update notification
     */
    function customDomainRequestStatusUpdate($saas_account_id)
    {
        $saas_account = SaasAccount::find((int)$saas_account_id);

        $status = "";
        if ($saas_account->customDomain->status == 0) {
            $status = 'pending';
        }
        if ($saas_account->customDomain->status == 1) {
            $status = 'approved';
        }
        if ($saas_account->customDomain->status == 2) {
            $status = 'cancelled';
        }

        $mail_data = [
            'template_id' => 25,
            'keywords' => getEmailTemplateVariables(25, true),
            'subject' => getSubjectByTemplateId(25),

            '_status_' => $status,
            '_custom_domain_' => $saas_account->customDomain->requested_domain,
            '_sub_domain_' => $saas_account->customDomain->current_domain,
            '_see_custom_domain_requests_' => url('/') . '/user/' . getSaasPrefix() . '/custom-domain'
        ];

        $user = User::find($saas_account->user_id);
        $data = [
            'message' => "Your custom domain request of " . $saas_account->customDomain->requested_domain . ' for ' . $saas_account->customDomain->current_domain . ' is ' . $status,
            'link' => '/user/' . getSaasPrefix() . '/custom-domain'
        ];
        $user->notify(new UserNotification($data));

        Mail::to($user->email)->send(new CommonSaasEmail($mail_data));
    }
}
/** Saas Customer Notification Ends */


/** Saas admin notification Starts */

if (!function_exists('newRegistrationNotificationAdmin')) {
    /**
     * will send new registration notification to admin
     */
    function newRegistrationNotificationAdmin($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $user = User::find($saas_account->user_id);

        $mail_data = [
            'template_id' => 19,
            'keywords' => getEmailTemplateVariables(19, true),
            'subject' => getSubjectByTemplateId(19),

            '_customer_name_' =>  $user->name,
            '_app_name_' =>  env('APP_NAME'),
        ];

        $admins = getAllAdminsToSendNotification(19);
        foreach ($admins as $admin) {
            $admin_user = User::find($admin->id);
            $data = [
                'message' => $user->name . ' has registered to our system',
                'link' => '/admin/' . getSaasPrefix() . '/all-stores'
            ];
            $admin_user->notify(new UserNotification($data));

            Mail::to($admin->email)->send(new CommonSaasEmail($mail_data));
        }
    }
}

if (!function_exists('newSubscriptionNotificationAdmin')) {
    /**
     * will send new subscription notification to admin
     */
    function newSubscriptionNotificationAdmin($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $user = User::find($saas_account->user_id);

        $mail_data = [
            'template_id' => 20,
            'keywords' => getEmailTemplateVariables(20, true),
            'subject' => getSubjectByTemplateId(20),

            '_customer_name_' =>  $user->name,
            '_package_name_' =>  $saas_account->package_name,
            '_plan_name_' =>  $saas_account->plan_name,
            '_member_type_' =>  $saas_account->membership_type,
            '_valid_till_' =>  $saas_account->valid_till,
            '_store_link_' =>  'https://' . $saas_account->domain
        ];

        $admins = getAllAdminsToSendNotification(20);
        foreach ($admins as $admin) {
            $admin_user = User::find($admin->id);
            $data = [
                'message' => $user->name . ' has subscribed into package: ' . $saas_account->package_name . ' plan: ' . $saas_account->plan_name . ' status: ' . ($saas_account->status == 1 ? 'Active' . ' Till: ' . $saas_account->valid_till : 'Inactive'),
                'link' => '/admin/' . getSaasPrefix() . '/all-stores'
            ];
            $admin_user->notify(new UserNotification($data));
            Mail::to($admin->email)->send(new CommonSaasEmail($mail_data));
        }
    }
}

if (!function_exists('changeSubscriptionNotificationAdmin')) {
    /**
     * will send change subscription notification to admin
     */
    function changeSubscriptionNotificationAdmin($saas_account_id)
    {
        $saas_account = getSaasAccountDetails($saas_account_id);

        $user = User::find($saas_account->user_id);

        $mail_data = [
            'template_id' => 22,
            'keywords' => getEmailTemplateVariables(22, true),
            'subject' => getSubjectByTemplateId(22),

            '_customer_name_' =>  $user->name,
            '_plan_name_' =>  $saas_account->plan_name,
            '_package_name_' =>  $saas_account->package_name,
            '_member_type_' =>  $saas_account->membership_type,
            '_valid_till_' =>  $saas_account->valid_till,
            '_store_name_' =>  $saas_account->store_name,
            '_store_link_' =>  'https://' . $saas_account->domain
        ];

        $admins = getAllAdminsToSendNotification(22);
        foreach ($admins as $admin) {
            $admin_user = User::find($admin->id);
            $data = [
                'message' => $user->name . ' has changed subscription to package: ' . $saas_account->package_name . ' plan: ' . $saas_account->plan_name . ' status: ' . ($saas_account->status == 1 ? 'Active' . ' Till: ' . $saas_account->valid_till : 'Inactive'),
                'link' => '/admin/' . getSaasPrefix() . '/all-stores'
            ];
            $admin_user->notify(new UserNotification($data));

            Mail::to($admin->email)->send(new CommonSaasEmail($mail_data));
        }
    }
}


if (!function_exists('newCustomDomainRequest')) {
    /**
     * will send new custom domain request notification
     *
     */
    function newCustomDomainRequest($saas_account_id)
    {
        $saas_account = SaasAccount::find((int)$saas_account_id);
        $mail_data = [
            'template_id' => 24,
            'keywords' => getEmailTemplateVariables(24, true),
            'subject' => getSubjectByTemplateId(24),

            '_custom_domain_' =>  $saas_account->customDomain->requested_domain,
            '_sub_domain_' =>  $saas_account->customDomain->current_domain,
            '_see_custom_domain_requests_' => url('/') . '/admin/' . getSaasPrefix() . '/custom-domain-request'
        ];

        $admins = getAllAdminsToSendNotification(25);
        foreach ($admins as $admin) {
            $admin_user = User::find($admin->id);
            $data = [
                'message' => "A new custom domain request of " . $saas_account->customDomain->requested_domain . " has come  for " . $saas_account->customDomain->current_domain,
                'link' => '/admin/' . getSaasPrefix() . '/custom-domain-request'
            ];
            $admin_user->notify(new UserNotification($data));

            Mail::to($admin->email)->send(new CommonSaasEmail($mail_data));
        }
    }
}

/** Saas admin notification Ends*/



if (!function_exists('getSaasAccountDetails')) {
    /**
     * will return saas account details by id
     */
    function getSaasAccountDetails($saas_account_id)
    {
        $saas_account_details = DB::table('tl_saas_accounts')
            ->join('tl_users', 'tl_users.id', '=', 'tl_saas_accounts.user_id')
            ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_accounts.package_id')
            ->leftJoin('tl_saas_package_plans', 'tl_saas_package_plans.id', '=', 'tl_saas_accounts.package_plan')
            ->where('tl_saas_accounts.id', '=', $saas_account_id)
            ->select([
                'tl_users.name as subscriber',
                'tl_saas_packages.name as package_name',
                'tl_saas_package_plans.name as plan_name',
                'tl_saas_accounts.membership_type',
                'tl_saas_accounts.valid_till',
                'tl_saas_accounts.store_name',
                'tl_saas_accounts.tenant_id',
                'tl_saas_accounts.user_id',
                'tl_saas_accounts.status',
                'tl_saas_accounts.id',
            ])->first();

        if ($saas_account_details != null) {
            if ($saas_account_details->valid_till == null) {
                $saas_account_details->valid_till = "Lifetime";
            }

            if ($saas_account_details->plan_name == null) {
                $saas_account_details->plan_name = "Lifetime";
            }

            $domain = DB::table('domains')->where('tenant_id', '=', $saas_account_details->tenant_id)->first();
            $saas_account_details->domain = $domain != null ? $domain->domain : null;
        }

        return $saas_account_details;
    }
}

if (!function_exists('systemHasPermissionToCreateDatabase')) {
    /**
     * will check if user has permission to create database
     */
    function systemHasPermissionToCreateDatabase()
    {
        if (env('HAS_DB_CREATE_PERMISSION') == 1) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('generateSlug')) {
    /**
     * generate slug from from string
     */
    function generateSlug($string)
    {
        // Convert the string to lowercase
        $string = strtolower($string);

        // Replace special characters with dashes
        $string = preg_replace('/[^a-z0-9]+/', '-', $string);

        // Remove leading and trailing dashes
        $string = trim($string, '-');

        // Remove consecutive dashes
        $string = preg_replace('/-+/', '-', $string);

        return $string;
    }
}

if (!function_exists('getSubjectByTemplateId')) {
    /**
     * get subject by template id
     */
    function getSubjectByTemplateId($template_id)
    {
        $email_template_properties = DB::table('tl_email_template_properties')
            ->where('tl_email_template_properties.email_type', '=', $template_id)
            ->first();
        return $email_template_properties == null ? '' : $email_template_properties->subject;
    }
}

if (!function_exists('getCurrentHostName')) {
    /**
     * get current host name
     */
    function getCurrentHostName()
    {
        $request = Request::instance();
        $host = $request->getHost();
        return $host;
    }
}

if (!function_exists('subscriptionScheduleNotification')) {
    /**
     * activate subscription schedule notification
     */
    function subscriptionScheduleNotification()
    {
        $notify_before_expired_days = \Plugin\Saas\Repositories\SettingsRepository::getSaasSetting('notify_before_expired_days');
        $notify_before_expired_interval_days = \Plugin\Saas\Repositories\SettingsRepository::getSaasSetting('notify_before_expired_interval_days');
        $saas_accounts = SaasAccount::all();

        foreach ($saas_accounts as $account) {
            if ($account->valid_till != null) {
                $saas_account = SaasAccount::find((int)$account->id);

                $valid_till = $account->valid_till;

                $currentDate = new DateTime(); // creates a DateTime object representing the current date and time
                $expired_date = new DateTime($valid_till); // creates a DateTime object from the date string

                $difference = $currentDate->diff($expired_date)->days; // calculates the difference between the two dates

                if ($expired_date >= $currentDate) {
                    if ($difference - $notify_before_expired_days == 0) {
                        if ($saas_account->is_notified != 1) {
                            subscriptionReminder($saas_account->id);
                            $saas_account->is_notified = 1;
                        }
                    } elseif (($notify_before_expired_days - $difference) % $notify_before_expired_interval_days == 0) {
                        if ($saas_account->is_notified != 1) {
                            subscriptionReminder($saas_account->id);
                            $saas_account->is_notified = 1;
                        }
                    } else {
                        $saas_account->is_notified = 0;
                    }
                } else {
                    if ($saas_account->is_notified != 1) {
                        subscriptionExpired($saas_account->id);
                        $saas_account->status = 0;
                        $saas_account->is_notified = 1;
                    }
                }

                $saas_account->update();
            }
        }
    }
}

if (!function_exists('getAllAdminsToSendNotification')) {
    /**
     * get all admins to send notification
     */
    function getAllAdminsToSendNotification($template_id)
    {
        $admins = DB::table('tl_saas_notifications_to_roles')
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'tl_saas_notifications_to_roles.role_id')
            ->join('tl_users', 'tl_users.id', '=', 'model_has_roles.model_id')
            ->where('tl_saas_notifications_to_roles.template_id', '=', $template_id)
            ->select([
                'tl_users.email',
                'tl_users.id'
            ])->get();
        return $admins;
    }
}

if (!function_exists('getAllSaasCurrencies')) {
    /**
     * get all currencies for saas
     */
    function getAllSaasCurrencies()
    {
        $currencies = DB::table('tl_saas_currencies')
            ->where('status', '=', 1)->get();
        return $currencies;
    }
}

if (!function_exists('getAllRoles')) {
    /**
     * get all roles
     */
    function getAllRoles()
    {
        $roles = DB::table('roles')->get();
        return $roles;
    }
}

if (!function_exists('generateUUID')) {
    /**
     * Generate unique ids
     */
    function generateUUID()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('isValidStoreName')) {
    /**
     * Check if store name is valid
     */
    function isValidStoreName($store_name)
    {
        $pattern = '/[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]/';

        if (!empty($store_name) && empty(preg_match($pattern, $store_name))) {
            $subdomain = implode('', explode(' ', strtolower($store_name)));


            $duplicate_store = DB::table('tl_saas_accounts')
                ->where('store_name', '=', $store_name);

            $duplicate_domain = DB::table('domains')
                ->where('domains.main_domain', '=', $subdomain . '.' . getCurrentHostName())
                ->orWhere('domains.main_domain', '=', $subdomain . '.' . getCurrentHostName());


            if ($duplicate_store->exists() || $duplicate_domain->exists()) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}

if (!function_exists('getAllSaasCurrencies')) {
    /**
     * Get all saas currency
     *
     *
     */
    function getAllSaasCurrencies()
    {
        $currencies = DB::table('tl_saas_currencies')
            ->where('status', '=', 1)
            ->select([
                'code',
                'name'
            ])->get();

        return $currencies;
    }
}

if (!function_exists('getSaasDefaultCurrency')) {
    /**
     * Get default currency
     *
     *
     */
    function getSaasDefaultCurrency()
    {
        $default_currency_id = SettingsRepository::getSaasSetting('default_currency');
        $default_currency = Currency::find((int)$default_currency_id);
        return $default_currency->code;
    }
}

if (!function_exists('resetSessionAndDatabase')) {
    /**
     * reset session & database on store creation
     */
    function resetSessionAndDatabase($error_occurred = false)
    {
        if ($error_occurred) {
            if (!session()->has('is_for_update') || !session()->get('is_for_update')) {
                if (session()->has('tenant_id')) {
                    DB::table('tenants')->where('id', '=', session()->get('tenant_id'))->delete();
                }
                if (session()->has('saas_account_id')) {
                    SaasAccount::find((int)session()->get('saas_account_id'))->delete();
                }
                if (session()->has('database')) {
                    DB::statement('DROP DATABASE ' . session()->get('database'));
                }
            } elseif (session()->has('saas_account_id') && session()->get('is_for_update')) {
                $account = SaasAccount::find((int)session()->get('saas_account_id'));
                $account->package_id = session()->get('previous_package_id');
                $account->package_plan = session()->get('previous_plan_id');
                $account->status = 1;
                $account->update();

                $repository = new CouponPackageRepository();
                $repository->updateSingleTenantDatabase($account->tenant_id, session()->get('previous_package_id'), session()->get('previous_plan_id'), 1);
            }
        }

        session()->forget('tenant_id');
        session()->forget('saas_account_id');
        session()->forget('database');
        session()->forget('is_for_update');
        session()->forget('previous_package_id');
        session()->forget('previous_plan_id');
    }
}

if (!function_exists('updateCouponInfo')) {
    /**
     * Will update coupon info
     */
    function updateCouponInfo($coupon_code)
    {
        DB::table('tl_saas_coupons')
            ->where('coupon_code', '=', $coupon_code)
            ->update(['status' => 1, 'total_used' => DB::raw('total_used + 1')]);
    }
}

if (!function_exists('isCustomer')) {
    /**
     * Will check if user is a customer or not
     */
    function isCustomer($id)
    {
        return DB::table('tl_saas_accounts')
            ->where('user_id', '=', $id)
            ->exists();
    }
}

if (!function_exists('dashboardDetails')) {
    /**
     * will return dashboard details
     */
    function dashboardDetails()
    {
        $total_stores = SaasAccount::count();
        $total_customers = User::where('user_type', config('saas.user_type.subscriber'))->count();
        $total_packages = Package::count();
        $total_plans = PackagePlan::count();
        $total_amount = PaymentHistory::where('status', 'paid')->sum('final_amount');

        $new_customers = User::where('user_type', config('saas.user_type.subscriber'))->orderBy('id', 'desc')->take(5)->get();
        $domain_request = CustomDomain::OrderBy('id', 'desc')->take(7)->get();

        $match_case = [];
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

        $sub_repo = new SubscriptionRepository();
        $stores = $sub_repo->getSaasAccountDetails($match_case, $data)->take(7)->get();

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

        $data = [
            'total_stores' => $total_stores,
            'total_customers' => $total_customers,
            'total_packages' => $total_packages,
            'total_plans' => $total_plans,
            'total_amount' => $total_amount,
            'new_customers' => $new_customers,
            'stores' => $stores,
            'domain_request' => $domain_request,
        ];

        return $data;
    }
}


if (!function_exists('translatePackageName')) {
    /**
     * will translated name of package
     */
    function translatePackageName($id)
    {
        $package = Package::find($id);
        return $package->translation('name', getLocale());
    }
}



if (!function_exists('jobExistsInQueue')) {
    /**
     * will check if any job exists in queue
     */
    function jobExistsInQueue()
    {
        $total_jobs = DB::table('jobs')->select(['payload'])->count();
        return $total_jobs;
    }
}

if (!function_exists('checkIfAnyStoreIsNotUpdated')) {
    /**
     * will check if any store is not updated
     */
    function checkIfAnyStoreIsNotUpdated()
    {
        $all_account_status = DB::table('tl_saas_accounts')
            ->where('is_system_db_updated', '=', 0)
            ->orWhere('is_plugin_db_updated', '=', 0)
            ->orWhere('is_db_updated', '=', 0)
            ->exists();

        return $all_account_status;
    }
}

if (!function_exists('rrmdir')) {
    /**
     * will remove directory
     */

    function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($dir . '/' . $object)) {
                        rrmdir($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}

if (!function_exists('isExtendedLicense')) {
    /**
     * check if extended lixense
     */

    function isExtendedLicense()
    {
        $license = DB::table('user_keys')->where('type', '!=', 'Regular')->exists();
        return $license;
    }
}

if (!function_exists('getEcommercePaymentGateways')) {
    /**
     * will retturn all ecommerce payment gateways
     */

    function getEcommercePaymentGateways()
    {
        $data = [];
        $payment_methods =  DB::table('tl_com_payment_methods')->select(['name', 'id'])->get();

        for ($i = 0; $i < sizeof($payment_methods); $i++) {
            $data[$payment_methods[$i]->name] = $payment_methods[$i]->id;
        }

        return $data;
    }
}
