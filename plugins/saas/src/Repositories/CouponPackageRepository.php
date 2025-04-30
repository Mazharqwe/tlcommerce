<?php

namespace Plugin\Saas\Repositories;

use Carbon\Carbon;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateSingleTenantDatabaseJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CouponPackageRepository
{
    /**
     * Will return packages by requested coupon type
     *
     * @return Collections
     */
    public function packagesOfCouponType($match_case, $data)
    {
        $query_builder = DB::table('tl_saas_coupons')
            ->join('tl_saas_coupons_of_packages', 'tl_saas_coupons_of_packages.coupon_id', 'tl_saas_coupons.id')
            ->join('tl_saas_packages', 'tl_saas_packages.id', '=', 'tl_saas_coupons_of_packages.package_id')
            ->where($match_case)
            ->select($data);

        return $query_builder;
    }

    /**
     * Will return all package plans
     */
    public function getPackagePlans($match_case, $data)
    {
        $packages = DB::table('tl_saas_package_plans')
            ->join('tl_saas_package_has_plans', 'tl_saas_package_has_plans.plan_id', '=', 'tl_saas_package_plans.id')
            ->groupBy('tl_saas_package_plans.id')
            ->where($match_case)
            ->select($data)->get();
        return $packages;
    }

    /**
     * Will return packages by plan
     */
    public function getPackagesByPlan($match_case, $data)
    {
        $packages = DB::table('tl_saas_packages')
            ->leftJoin('tl_saas_package_has_plans', 'tl_saas_package_has_plans.package_id', '=', 'tl_saas_packages.id')
            ->leftJoin('tl_saas_package_plans', 'tl_saas_package_plans.id', '=', 'tl_saas_package_has_plans.plan_id')
            ->orderBy('tl_saas_package_has_plans.cost', 'asc')
            ->orderBy('tl_saas_packages.id', 'desc')
            ->where($match_case)
            ->select($data)
            ->get();

        return $packages;
    }

    /**
     * Will return all plugins of a package
     */
    public function getPluginsOfPackage($package_id)
    {
        $plugins = DB::table('tl_saas_package_has_plugins')
            ->join('tl_plugins', 'tl_plugins.id', '=', 'tl_saas_package_has_plugins.plugin_id')
            ->where('tl_saas_package_has_plugins.package_id', '=', $package_id)
            ->select([
                'tl_plugins.id as plugin_id',
                'tl_plugins.name as plugin_name',
            ])->get();

        return $plugins;
    }

    /**
     * Will return all privileges of a package
     */
    public function getPrivilegesOfPackage($package_id)
    {
        $privileges = DB::table('tl_saas_package_has_privileges')
            ->where('tl_saas_package_has_privileges.package_id', '=', $package_id)
            ->select([
                'tl_saas_package_has_privileges.privileges',
            ])->first();

        if ($privileges != null) {
            return $privileges->privileges;
        }

        return $privileges;
    }

    /**
     * Will return all payment methods of a package
     */
    public function getPaymentMethodsOfPackage($package_id)
    {
        $payment_gateways = DB::table('tl_saas_package_has_payment_methods')
            ->where('tl_saas_package_has_payment_methods.package_id', '=', $package_id)
            ->select([
                'tl_saas_package_has_payment_methods.payment_method_id as payment_method',
            ])->get();

        return $payment_gateways;
    }

    /**
     * Will return all plannings of a package
     */
    public function getPlanningsOfPackage($package_id)
    {
        $plannings = DB::table('tl_saas_package_plans')
            ->join('tl_saas_package_has_plans', 'tl_saas_package_has_plans.plan_id', '=', 'tl_saas_package_plans.id')
            ->where('tl_saas_package_has_plans.package_id', '=', $package_id)
            ->select([
                'tl_saas_package_plans.name',
                'tl_saas_package_has_plans.cost',
                'tl_saas_package_plans.id'
            ])
            ->get();

        return $plannings;
    }

    /**
     * Will update tenant database
     */
    public function updateTenantDatabase($package_id)
    {
        $all_saas_account = DB::table('tl_saas_packages')
            ->join('tl_saas_accounts', 'tl_saas_accounts.package_id', '=', 'tl_saas_packages.id')
            ->where('tl_saas_packages.id', '=', $package_id)
            ->where('tl_saas_accounts.is_db_created', '=', 1);

        $all_saas_account->update([
            'is_db_updated' => 0
        ]);

        $all_saas_account = $all_saas_account->pluck('tl_saas_accounts.id as saas_account_id');

        for ($i = 0; $i < sizeof($all_saas_account); $i++) {
            $job = new UpdateSingleTenantDatabaseJob($all_saas_account[$i], $package_id, 1);
            dispatch($job);
        }
    }

    /**
     * Will update single tenant database
     */
    public function updateSingleTenantDatabase($tenant_id, $package_id, $saas_account_id, $is_for_update, $is_from_admin = false)
    {
        if ((systemHasPermissionToCreateDatabase() && $is_for_update == 0) || ($is_for_update == 0 && $is_from_admin)) {
            $tenant = Tenant::find($tenant_id);
            $database = $tenant->tenancy_db_name;
            $tenantConfig = config('database.connections.tenant');
            $tenantConfig['database'] = $database;
            $tenantConfig['log'] = true;
            $tenantConnectionName = 'tenant_' . $database;
            config(["database.connections.$tenantConnectionName" => $tenantConfig]);
            session()->put('tenant_connection_name', $tenantConnectionName);

            $query = DB::connection($tenantConnectionName);

            if (systemHasPermissionToCreateDatabase()) {
                $sql = 'CREATE DATABASE IF NOT EXISTS ' . $database . ';';
                DB::unprepared($sql);
            }


            $this->dropAllTables($query, $database);
            $sqlFilePath = storage_path('app/tenant.sql');
            $sqlContent = file_get_contents($sqlFilePath);
            $query->unprepared($sqlContent);

            $job = new UpdateSingleTenantDatabaseJob($saas_account_id, $package_id, $is_for_update);
            dispatch($job);
        }
        if ($is_for_update == 1) {
            $job = new UpdateSingleTenantDatabaseJob($saas_account_id, $package_id, $is_for_update);
            dispatch($job);
        }
    }

    /**
     * Will insert third party plugin tables
     */
    public function insertThirdPartyPluginTables($query)
    {
        $all_plugins_location = DB::table('tl_plugins')->where('type', '=', 'outside')->pluck('location')->toArray();
        $installed_plugins_locations = $query->table('tl_plugins')->pluck('location')->toArray();
        $uninstalled_plugin_locations = array_diff($all_plugins_location, $installed_plugins_locations);

        foreach ($uninstalled_plugin_locations as $location) {
            $sql_path = base_path('plugins/' . $location . '/data.sql');
            if (file_exists($sql_path)) {
                $sql = file_get_contents($sql_path);
                $query->unprepared($sql);
            }
        }
    }

    /**
     * Update tenant plugin database
     */
    public function updateAllTenantPluginData($query, $package_id)
    {
        try {
            $tlcommerce_plugin_location = DB::table('tl_plugins')->where('location', '=', 'tlecommercecore')->value('location');
            $plugin_locations = DB::table('tl_saas_package_has_plugins')
                ->join('tl_plugins', 'tl_plugins.id', '=', 'tl_saas_package_has_plugins.plugin_id')
                ->where('package_id', '=', $package_id)
                ->pluck('tl_plugins.location')
                ->toArray();

            array_push($plugin_locations, $tlcommerce_plugin_location);

            if (!empty($plugin_locations)) {
                $query->table('tl_plugins')
                    ->whereIn('location', $plugin_locations)
                    ->update(['is_activated' => 1]);

                $query->table('tl_plugins')
                    ->whereNotIn('location', $plugin_locations)
                    ->update(['is_activated' => 0]);
            } else {
                $query->table('tl_plugins')->update(['is_activated' => 0]);
            }
        } catch (\Exception $ex) {
            $error = [
                'message' => 'Error occured during updating tenant plugin data',
                'data' => $query,
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            $query->rollback();
        }
    }

    /**
     * Update tenant payment method data
     */
    public function updateAllTenantPaymentMethodData($query, $package_id)
    {
        try {
            $permitted_payment_methods = DB::table('tl_saas_package_has_payment_methods')->where('package_id', '=', $package_id)->pluck('payment_method_id')->toArray();
            if (!empty($permitted_payment_methods)) {
                $query->table('tl_com_payment_methods')
                    ->update([
                        'status' => DB::raw("CASE WHEN id IN (" . implode(',', $permitted_payment_methods) . ") THEN 1 ELSE 0 END")
                    ]);
            } else {
                $query->table('tl_com_payment_methods')->update(['status' => 0]);
            }
        } catch (\Exception $ex) {
            $error = [
                'message' => 'Error occured during updating tenant payment method data',
                'data' => $query,
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            $query->rollback();
        }
    }

    /**
     * Register user for newly created store
     */
    public function registerUserForNewStore($query, $saas_account)
    {
        try {
            $date = Carbon::now();
            $user_id = $date->format('y') . $date->format('m') . $date->format('d') . $date->format('h') . $date->format('m') . $date->format('s');
            $user_name = $saas_account->user->name;
            $user_email = $saas_account->user->email;
            $user_password = $saas_account->user->password;
            $user_status = config('settings.user_status.active');
            $user_role = config('settings.roles.supper_admin');

            //Set system name
            $user_data = [
                'name' => $user_name,
                'email' => $user_email,
                'user_type' => 1,
                'password' => $user_password,
                'status' => $user_status
            ];

            $inserted_id = $query->table('tl_users')->insertGetId($user_data);
            $user_uid = "SUPER-ADMIN-" . $inserted_id . $user_id;
            $query->table('tl_users')->update([
                'uid' => $user_uid
            ]);

            $user_role_data = [
                'role_id' => $user_role,
                'model_type' => 'Core\Models\User',
                'model_id' => $inserted_id
            ];

            $query->table('model_has_roles')->insert($user_role_data);

            $settings_data = [
                'settings_id' => getGeneralSettingId('system_name'),
                'value' => $saas_account->store_name
            ];

            $query->table('tl_general_settings_has_values')->where('settings_id', getGeneralSettingId('system_name'))->delete();
            $query->table('tl_general_settings_has_values')->insert($settings_data);

            $query->table('tl_com_products')->update([
                'supplier' => $inserted_id
            ]);

            $query->table('tl_com_ordered_products')->update([
                'seller_id' => $inserted_id
            ]);

            $query->table('tl_general_settings_has_values')->insert([
                'settings_id' => 359,
                'value' => $saas_account->tenant_id
            ]);

            $query->commit();
        } catch (\Exception $ex) {
            $error = [
                'message' => 'Error occured during registering default user for tenant panel',
                'data' => $saas_account,
                'error' => $ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));
            $query->rollback();
        }
    }

    /**
     * Drop table if exists
     */
    public function dropAllTables($query, $database)
    {
        // Disable foreign key checks temporarily
        $query->statement('SET FOREIGN_KEY_CHECKS=0');

        // Get the list of tables in the database
        $tables = $query->select("SHOW TABLES FROM {$database}");

        foreach ($tables as $table) {
            $table = reset($table); // Extract the table name from the result

            // Drop each table
            $query->statement("DROP TABLE IF EXISTS `{$table}`");
        }

        // Enable foreign key checks again
        $query->statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
