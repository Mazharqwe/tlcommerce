<?php

namespace Plugin\Saas\Http\Controllers\User;


use Exception;
use Carbon\Carbon;
use Core\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Core\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Core\Mail\EmailPasswordResetLink;
use Core\Models\AdminLoginActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Plugin\Saas\Repositories\CouponPackageRepository;
use Plugin\Saas\Repositories\SubscriptionRepository;

class UserController extends Controller
{

    protected $subscription_repository;
    protected $cp_repo;

    public function __construct(SubscriptionRepository $repository, CouponPackageRepository $cp_repo)
    {
        $this->subscription_repository = $repository;
        $this->cp_repo = $cp_repo;
    }

    /**
     * will redirect to registration page
     * @return mixed
     */
    public function register()
    {
        if (Auth::user()) {
            return redirect()->route('plugin.saas.user.dashboard');
        } else {
            return view('plugin/saas::user.panel.auth.register');
        }
    }

    /**
     * Will store requested user information
     */
    public function storeUserDetails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:tl_users,name',
                'email' => 'required|unique:tl_users,email',
                'password' => 'required|min:6|confirmed',
                'coupon' => 'nullable|exists:tl_saas_coupons,coupon_code',
                'package_id' => 'required|exists:tl_saas_packages,id',
                'store_name' => 'required|unique:tl_saas_accounts,store_name'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            if (!isValidStoreName($request['store_name'])) {
                toastNotification('error', translate('Invalid/Duplicate Store Name!'));
                return back();
            }

            DB::beginTransaction();
            $user = new User();
            $user->uid = "SUBSCRIBER-" . time();
            $user->name = $request['name'];
            $user->email = $request['email'];
            $user->password = Hash::make($request['password']);
            $user->status = 1;
            $user->user_type = config('saas.user_type.subscriber');
            $user->save();
            session()->put('user_id', $user->id);

            $is_free_package = DB::table('tl_saas_packages')
                ->where('id', '=', $request['package_id'])
                ->where('type', '=', 'free')
                ->exists();

            if (!$is_free_package && !empty($request['plan_id'])) {
                $validator = Validator::make($request->all(), [
                    'plan_id' => 'required|exists:tl_saas_package_plans,id'
                ]);
                if ($validator->fails()) {
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }
                if (!empty($request['coupon'])) {
                    $package_details = $this->subscription_repository->getDetailsForSubscriptionAccount($request, false);
                } else {
                    $package_details = $this->subscription_repository->getDetailsForSubscriptionAccount($request, true);
                }
            } else {
                $package_details = $this->subscription_repository->getDetailsForFreeSubscriptionAccount($request);
            }

            session()->put('is_for_update', false);
            $tenant = $this->subscription_repository->createNewTenantAndDatabase($request);
            $saas_account = $this->subscription_repository->storeSaasAccountDetails($package_details, $user, $tenant);

            if (isset($request['coupon'])) {
                updateCouponInfo($request['coupon']);
            }

            $this->sendSubscriptionNotification($saas_account->id);
            newRegistrationNotificationAdmin($saas_account->id);
            newSubscriptionNotificationAdmin($saas_account->id);

            DB::commit();
            $this->cp_repo->updateSingleTenantDatabase($tenant->id, $request['package_id'], $saas_account->id, 0);
            toastNotification('success', translate('Registration Successful!'));
            return redirect()->route('subscriber.login');
        } catch (Exception $ex) {
            $error = [
                'message'=>'Error occured during subscriber registration',
                'data'=>request()->all(),
                'error'=>$ex
            ];
            Log::channel('tenant_database')->info(json_encode($error));

            DB::rollBack();
            toastNotification('error', translate('Registration Unsuccessful!'));
            return back();
        }
    }

    /**
     * redirect to login page
     *
     * @return mixed
     */
    public function login()
    {
        if (isset(request()->message)) {
            toastNotification('error', request()->message);
        }
        if (Auth::user()) {
            if (Auth::user()->user_type == config('saas.user_type.subscriber')) {
                return redirect()->route('plugin.saas.user.dashboard');
            } else {
                return redirect()->route('admin.dashboard');
            }
        } else {
            return view('plugin/saas::user.panel.auth.login');
        }
    }


    /**
     * attempt to Login
     *
     * @param  mixed $request
     * @return mixed
     */
    public function attemptLogin(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $this->setupLoginLogoutActivity(true);
            if (Auth::user()->status == config('settings.user_status.in_active')) {
                $this->logout();
                toastNotification('error', translate("Your status is currently inactive"));
                return redirect()->back();
            } elseif (Auth::user()->user_type == config('saas.user_type.subscriber')) {
                toastNotification('success', translate('Login successful'));
                return redirect()->route('plugin.saas.user.dashboard');
            } else {
                $this->logout();
                toastNotification('error', translate("Login Credentials Does not Match"));
                return redirect()->back();
            }
        }
        toastNotification('error', translate("Login Credentials Does not Match"));
        return redirect()->back();
    }

    /**
     * Attempt logout
     *
     * @return mixed
     */
    public function logout()
    {
        $this->setupLoginLogoutActivity(false);
        Auth::logout();
        return redirect()->route('subscriber.login');
    }

    /**
     * redirect to password reset page
     *
     * @return mixed
     */
    public function passwordResetLink()
    {
        if (Auth::user()) {
            if (Auth::user()->user_type == config('saas.user_type.subscriber')) {
                return redirect()->route('plugin.saas.user.dashboard');
            } else {
                return redirect()->route('admin.dashboard');
            }
        } else {
            return view('plugin/saas::user.panel.auth.password_reset_link');
        }
    }

    /**
     * will send password reset link to user email address
     *
     * @param  mixed $request
     * @return mixed
     */
    public function emailResetPasswordLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tl_users',
        ]);
        try {
            $token = Str::random(64);

            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            $template = DB::table('tl_email_template_properties')
                ->where('email_type', config('settings.email_template.reset_user_password'))
                ->select([
                    'subject'
                ])->first();

            $data = [
                'template_id' => config('settings.email_template.reset_user_password'),
                'keywords' => getEmailTemplateVariables(config('settings.email_template.reset_user_password'), true),
                'subject' => $template->subject,
                '_reset_password_link_' => route('core.reset.password', $token)
            ];

            Mail::to($request->email)->send(new EmailPasswordResetLink($data));
            toastNotification('success', translate('We have e-mailed your password reset link'));
            return back();
        } catch (Exception $ex) {
            toastNotification('success', translate('Unable to send email !'));
            return back();
        }
    }

    /**
     * reset password
     *
     * @param  mixed $token
     * @return mixed
     */
    public function resetPassword($token)
    {
        return view('plugin/saas::user.panel.auth.reset_password', ['token' => $token]);
    }

    /**
     * reset password
     *
     * @param  mixed $request
     * @return mixed
     */
    public function resetPasswordPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:tl_users',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ]);

        try {
            $updatePassword = DB::table('password_resets')
                ->where([
                    'email' => $request->email,
                    'token' => $request->token
                ])->first();

            if (!$updatePassword) {
                toastNotification('error', translate('Invalid token'));
                return back();
            }

            $user_details = DB::table('tl_users')->where('email', '=', $request['email'])->first();
            $user = User::find($user_details->id);
            $user->password = Hash::make($request['password']);
            $user->update();

            DB::table('password_resets')->where(['email' => $request->email])->delete();

            toastNotification('success', translate('Your password has been reset'));
            return redirect()->route('subscriber.login');
        } catch (\Exception $th) {
            toastNotification('error', translate('Unable to reset password'));
            return back();
        }
    }

    /**
     * setup login logout activity
     *
     * @param  mixed $is_for_login
     * @return mixed
     */
    public function setupLoginLogoutActivity($is_for_login)
    {
        if ($is_for_login) {
            $user_ip_address = getUserIpAddr();
            $os = get_operating_system();
            $browser = get_browser_name();
            $user_id = Auth::user()->id;
            $user_name = Auth::user()->name;

            $login_activity = new AdminLoginActivityLog();
            $login_activity->user_id = $user_id;
            $login_activity->login_at = Carbon::now()->toDateTimeString();
            $login_activity->os = $os;
            $login_activity->browser = $browser;
            $login_activity->ip = $user_ip_address;
            $login_activity->saveOrFail();

            Session::put($user_name, $login_activity->id);
        } else {
            $user_name = Auth::user()->name;
            $login_activity_id = Session::get($user_name);
            $login_activity = AdminLoginActivityLog::find($login_activity_id);
            if ($login_activity != null) {
                $login_activity->logout_at = Carbon::now()->toDateTimeString();
                $login_activity->update();
            }
        }
    }

    /**
     * Send subscription notification after registration
     */
    public function sendSubscriptionNotification($saas_account_id)
    {
        registrationSuccessfulNotification($saas_account_id);
        newSubscription($saas_account_id);

        return redirect()->route('plugin.saas.user.dashboard');
    }
}
