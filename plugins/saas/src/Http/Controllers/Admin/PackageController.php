<?php

namespace Plugin\Saas\Http\Controllers\Admin;


use Exception;
use Illuminate\Http\Request;
use Plugin\Saas\Models\Package;
use Illuminate\Support\Facades\DB;
use Plugin\Saas\Models\PackagePlan;
use Plugin\Saas\Models\SaasAccount;
use App\Http\Controllers\Controller;
use Core\Repositories\LanguageRepository;
use Illuminate\Support\Facades\Validator;
use Plugin\Saas\Models\TLSaasPackageTranslations;
use Plugin\Saas\Repositories\CouponPackageRepository;

class PackageController extends Controller
{
    protected $coupon_package_repo;
    protected $language_repository;
    public function __construct(LanguageRepository $language_repository, CouponPackageRepository $coupon_package_repo)
    {
        $this->coupon_package_repo = $coupon_package_repo;
        $this->language_repository = $language_repository;
    }
    /**
     * will redirect to package creation page
     *
     * @return mixed
     */
    public function createPackage()
    {
        return view('plugin/saas::package.create_package');
    }

    /**
     * store new package
     */
    public function storePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:tl_saas_packages,name|max:50',
                'is_featured' => 'required'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            if ($request->type == 'paid') {
                $validator = Validator::make($request->all(), [
                    'plans' => 'required',
                    'trail_period' => 'required'
                ]);

                if ($validator->fails()) {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }
            }

            DB::beginTransaction();

            $package = new Package();
            $package->type = $request->type;
            $package->name = $request->name;
            $package->is_featured = $request->is_featured;
            $package->status = $request->status;
            $package->trail_period = $request->type == 'paid' ? $request->trail_period : 0;
            $package->save();

            $this->savePackagePlugins($package->id, $request);
            $this->savePackagePrivileges($package->id, $request);
            $this->savePackagePaymentMethods($package->id, $request);

            if ($request->type == 'paid') {
                $this->savePackagePlans($request, $package);
            }

            DB::commit();
            toastNotification('success', translate('Package Created Successfully!'));
            return back();
        } catch (Exception $ex) {
            DB::rollback();
            toastNotification('error', translate('Package creation failed!'));
            return back();
        }
    }

    /**
     * Will save package plans
     */
    public function savePackagePlans($request, $package)
    {
        $plans = $request['plans'];
        $cost = $request['cost'];
        $data = [];

        for ($i = 0; $i < sizeof($plans); $i++) {
            $array = [
                'package_id' => $package->id,
                'plan_id' => $plans[$i],
                'cost' => $cost[$i] == null ? 0 : $cost[$i],
            ];
            array_push($data, $array);
        }
        DB::table('tl_saas_package_has_plans')->insert($data);
    }

    /**
     * Will save package plugins
     */
    public function savePackagePlugins($package_id, $request, $is_for_update = false)
    {
        if ($is_for_update) {
            DB::table('tl_saas_package_has_plugins')
                ->where('package_id', '=', $package_id)
                ->delete();
        }

        $package_plugins = $request->all();
        $filtered_plugins = array_filter($package_plugins, function ($key) {
            return strpos($key, 'package_plugins_') !== false;
        }, ARRAY_FILTER_USE_KEY);
        $data = [];

        foreach ($filtered_plugins as $value) {
            $array = [
                'package_id' => $package_id,
                'plugin_id' => $value
            ];
            array_push($data, $array);
        }
        DB::table('tl_saas_package_has_plugins')->insert($data);
    }

    /**
     * Will save package payment methods
     */
    public function savePackagePaymentMethods($package_id, $request, $is_for_update = false)
    {
        if ($is_for_update) {
            DB::table('tl_saas_package_has_payment_methods')
                ->where('package_id', '=', $package_id)
                ->delete();
        }

        $package_payment_methods = $request->all();
        $filtered_payment_methods = array_filter($package_payment_methods, function ($key) {
            return strpos($key, 'package_payment_method_') !== false;
        }, ARRAY_FILTER_USE_KEY);
        $data = [];

        foreach ($filtered_payment_methods as $value) {
            $array = [
                'package_id' => $package_id,
                'payment_method_id' => $value
            ];
            array_push($data, $array);
        }
        DB::table('tl_saas_package_has_payment_methods')->insert($data);
    }

    /**
     * Will save package privileges
     */
    public function savePackagePrivileges($package_id, $request, $is_for_update = false)
    {
        if ($is_for_update) {
            DB::table('tl_saas_package_has_privileges')
                ->where('package_id', '=', $package_id)
                ->delete();
        }

        $package_privileges = $request->all();



        $filtered_privileges = array_filter($package_privileges, function ($key) {
            return strpos($key, 'package_privileges_') !== false;
        }, ARRAY_FILTER_USE_KEY);



        foreach ($filtered_privileges as $key => $privileges) {
            $filtered_privileges[$key] = (int)$privileges;
        }


        $data = [];


        $array = [
            'package_id' => $package_id,
            'privileges' => json_encode($filtered_privileges)
        ];
        array_push($data, $array);

        DB::table('tl_saas_package_has_privileges')->insert($data);
    }

    /**
     * Will return package listings
     */
    public function packages()
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

            $first_plan = -1;

            if (sizeof($package_plans) > 0) {
                $first_plan = $package_plans[0]->id;
            }
            return view('plugin/saas::package.index', compact('package_plans', 'first_plan'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch package plans!'));
            return back();
        }
    }


    /**
     * Will return packages according to plan
     */
    public function getPackageAccordingToPlan(Request $request)
    {
        $free_packages = $this->getFreePackages();
        $paid_packages = $this->getPaidPackages($request);
        $plan_id = $request['plan_id'];

        if (!isset($request['is_for_payment'])) {
            return view('plugin/saas::package.include.package_list', compact('free_packages', 'paid_packages', 'plan_id'));
        } else {
            $store_id = $request['store_id'];
            if ($store_id != 'null') {
                $current_plan = getCurrentPlan($store_id);
            } else {
                $current_plan = null;
            }
            return view('plugin/saas::user.panel.subscription.include.package_list', compact('free_packages', 'paid_packages', 'plan_id', 'current_plan', 'store_id'));
        }
    }

    /**
     * Will return packages according to plan for frontend
     */
    public function getPackageAccordingToPlanForFrontend(Request $request)
    {
        $free_packages = $this->getFreePackages();
        $paid_packages = $this->getPaidPackages($request);
        $plan_id = $request['plan_id'];
        $subscribe_btn_text = $request['subscribe_btn_text'];

        return view('plugin/pagebuilder::builders.builder-widgets.include.package_list', compact('free_packages', 'paid_packages', 'plan_id', 'subscribe_btn_text'));
    }

    /**
     * Will return free packages
     */
    public function getFreePackages()
    {
        $match_case = [
            ['tl_saas_packages.type', '=', 'free']
        ];
        $data = [
            'tl_saas_packages.name', 'tl_saas_packages.type',
            'tl_saas_packages.id',
            DB::raw('0 as cost'),
            DB::raw('"" as plugins'),
            DB::raw('"" as privileges'),
            DB::raw('"" as payment_methods'),
        ];
        $free_packages = $this->coupon_package_repo->getPackagesByPlan($match_case, $data);

        for ($i = 0; $i < sizeof($free_packages); $i++) {
            $package = $free_packages[$i];
            foreach ($package as $key => $value) {
                if ($key == 'plugins') {
                    $package->$key = $this->coupon_package_repo->getPluginsOfPackage($package->id);
                }
                if ($key == 'privileges') {
                    $package->$key = json_decode($this->coupon_package_repo->getPrivilegesOfPackage($package->id));
                }
                if ($key == 'payment_methods') {
                    $package->$key = $this->coupon_package_repo->getPaymentMethodsOfPackage($package->id);
                }
            }
        }
        return $free_packages;
    }

    /**
     * Will return paid packages
     */
    public function getPaidPackages($request)
    {
        $plan_id = $request['plan_id'];
        $match_case = [
            ['tl_saas_packages.type', '=', 'paid'],
            ['tl_saas_package_plans.id', '=', (int)$plan_id]
        ];

        if ($request['is_for_payment'] == 1) {
            $match_case = [
                ['tl_saas_packages.type', '=', 'paid'],
                ['tl_saas_package_plans.id', '=', (int)$plan_id],
                ['tl_saas_packages.status', '=', 1]
            ];
        }


        $data = [
            'tl_saas_packages.name', 'tl_saas_packages.type',
            'tl_saas_packages.id', 'tl_saas_package_plans.id as plan_id',
            'tl_saas_package_plans.duration', 'tl_saas_package_plans.name as plan_name',
            'tl_saas_package_has_plans.cost',
            DB::raw('"" as plugins'),
            DB::raw('"" as privileges'),
            DB::raw('"" as payment_methods'),
        ];
        $paid_packages = $this->coupon_package_repo->getPackagesByPlan($match_case, $data);

        for ($i = 0; $i < sizeof($paid_packages); $i++) {
            $package = $paid_packages[$i];
            foreach ($package as $key => $value) {
                if ($key == 'plugins') {
                    $package->$key = $this->coupon_package_repo->getPluginsOfPackage($package->id);
                }
                if ($key == 'privileges') {
                    $package->$key = $this->coupon_package_repo->getPrivilegesOfPackage($package->id);
                }
                if ($key == 'payment_methods') {
                    $package->$key = $this->coupon_package_repo->getPaymentMethodsOfPackage($package->id);
                }
            }
        }

        return $paid_packages;
    }

    /**
     * Will return all plans of requested package
     */
    public function getPlansAccordingToPackage(Request $request)
    {
        try {
            $data = [
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.id)) as id'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.name)) as name'),
                DB::raw('GROUP_CONCAT(DISTINCT(tl_saas_package_plans.duration)) as duration')
            ];

            $match_case = [
                ['tl_saas_package_has_plans.package_id', '=', $request['package_id']]
            ];

            $all_package_plans = $this->coupon_package_repo->getPackagePlans($match_case, $data);

            return response()->json([
                'success' => true,
                'plans' => $all_package_plans,
                'message' => translate('Data retrieved successfully')
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => translate('Data retrieved Unsuccessful')
            ]);
        }
    }

    /**
     * Edit Package
     */
    public function editPackage(Request $request, $id)
    {
        try {

            $languages = $this->language_repository->allLanguages();
            $lang =  $request->lang;


            $package = Package::find($id);

            $selected_planing = $this->coupon_package_repo->getPlanningsOfPackage($package->id);

            $selected_planing_id = DB::table('tl_saas_package_has_plans')
                ->where('tl_saas_package_has_plans.package_id', '=', $id)
                ->pluck('tl_saas_package_has_plans.plan_id')->toArray();

            $package_features = $this->coupon_package_repo->getPluginsOfPackage($package->id)->toArray();
            $package_privileges = $this->coupon_package_repo->getPrivilegesOfPackage($package->id);
            $package_payment_methods = $this->coupon_package_repo->getPaymentMethodsOfPackage($package->id)->toArray();

            return view('plugin/saas::package.edit_package', compact('languages', 'lang', 'package', 'selected_planing_id', 'selected_planing', 'package_features', 'package_privileges', 'package_payment_methods'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch package details!'));
            return back();
        }
    }

    /**
     * Will update package
     */
    public function updatePackage(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:tl_saas_packages,name,' . $request->id . '|max:50',
                'is_featured' => 'required'
            ]);

            if ($validator->fails()) {
                toastNotification('error', translate('Please give valid information !'));
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            if ($request->type == 'paid') {
                $validator = Validator::make($request->all(), [
                    'plans' => 'required',
                    'trail_period' => 'required'
                ]);

                if ($validator->fails()) {
                    toastNotification('error', translate('Please give valid information !'));
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                $subscriber_package = DB::table('tl_saas_accounts')
                    ->where('package_id', '=', $request['id'])
                    ->whereNotIn('package_plan', $request['plans']);

                if ($subscriber_package->exists()) {
                    toastNotification('error', translate('You cannot remove package plan as some subscriber is already in this plan!'));
                    return back();
                }
            }
            if ($request['lang'] != null && $request['lang'] != getDefaultLang()) {
                $package_translation = TLSaasPackageTranslations::firstOrNew(['package_id' => $request['id'], 'lang' => $request['lang']]);
                $package_translation->name = xss_clean($request['name']);
                $package_translation->save();
            } else {
                $package = Package::find($request->id);
                $package->type = $request->type;
                $package->name = $request->name;
                $package->is_featured = $request->is_featured;
                $package->status = $request->status;
                $package->trail_period = $request->type == 'paid' ? $request->trail_period : 0;

                $package->update();

                $this->savePackagePlugins($package->id, $request, true);
                $this->savePackagePrivileges($package->id, $request, true);
                $this->savePackagePaymentMethods($package->id, $request, true);

                if ($request->type == 'paid') {
                    DB::table('tl_saas_package_has_plans')
                        ->where('package_id', '=', $package->id)
                        ->delete();

                    $this->savePackagePlans($request, $package);
                }

                $this->coupon_package_repo->updateTenantDatabase($request->id);
            }

            DB::commit();
            toastNotification('success', translate('Package Updated Successfully!'));
            return back();
        } catch (Exception $ex) {
            DB::rollBack();
            toastNotification('error', translate('Package update failed!'));
            return back();
        }
    }

    /**
     * Delete Package
     */
    public function deletePackage(Request $request)
    {
        $subscriber_package = SaasAccount::where('package_id', (int)$request['id']);
        if ($subscriber_package->exists()) {
            toastNotification('error', translate('You cannot delete this package as subscribers are already using this package!'));
            return back();
        }
        try {
            DB::beginTransaction();
            $package = Package::find($request->id);
            $package->delete();
            DB::commit();
            toastNotification('success', translate('Package Deleted Successfully!'));
            return back();
        } catch (\Exception $ex) {
            DB::rollBack();
            toastNotification('error', translate('Package Delete Unsuccessful!'));
            return back();
        }
    }

    /**
     * All package plans
     */
    public function packagePlans()
    {
        try {
            $plans = DB::table('tl_saas_package_plans')
                ->orderBy('tl_saas_package_plans.id', 'desc')
                ->select(['name', 'duration', 'id'])
                ->get();

            return view('plugin/saas::package.plan', compact('plans'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch package plans!'));
            return back();
        }
    }

    /**
     * Store package plan
     */
    public function storePackagePlans(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_name' => 'required|unique:tl_saas_package_plans,name|max:50',
                'plan_duration' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => "Unable to create new plan"
                ]);
            }

            DB::beginTransaction();

            $plan = new PackagePlan();
            $plan->name = xss_clean($request['plan_name']);
            $plan->duration = $request['plan_duration'];
            $plan->save();

            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => [],
                'message' => translate("Unable to create new plan")
            ]);
        }
    }

    /**
     * Will request to update package plan
     */
    public function updatePackagePlan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'plan_name' => 'required|unique:tl_saas_package_plans,name,' . $request->id . '|max:50',
                'plan_duration' => 'required|numeric|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => "Unable to update plan"
                ]);
            }

            DB::beginTransaction();

            $plan = PackagePlan::find($request['id']);
            $plan->name = xss_clean($request['plan_name']);
            $plan->duration = $request['plan_duration'];
            $plan->update();

            DB::commit();

            return response()->json([
                'success' => true
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => [],
                'message' => translate("Unable to update plan")
            ]);
        }
    }

    /**
     * Delete Package Plan
     */
    public function deletePackagePlan(Request $request)
    {
        try {
            DB::beginTransaction();
            $package_plan = PackagePlan::find($request->id);
            $package_plan->delete();
            DB::commit();
            toastNotification('success', translate('Package Plan Deleted Successfully!'));
            return back();
        } catch (Exception $ex) {
            DB::rollBack();
            toastNotification('error', translate('Package Delete Unsuccessful!'));
            return back();
        }
    }
}
