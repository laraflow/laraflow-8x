<?php

namespace App\Services\Backend\Auth;

use App\Http\Requests\Backend\Auth\LoginRequest;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Class AuthenticatedSessionService
 */
class AuthenticatedSessionService
{
    /**
     * @var PasswordResetService
     */
    private $passwordResetService;

    /**
     * @param  PasswordResetService  $passwordResetService
     * @return void
     */
    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Handle an incoming auth request.
     *
     * @param  LoginRequest  $request
     * @return array
     */
    public function attemptLogin(LoginRequest $request): array
    {
        $authConfirmation = $this->ensureIsNotRateLimited($request);

        if ($authConfirmation['status'] == true) {
            //Count Overflow Request hit
            RateLimiter::hit($this->throttleKey($request));

            $authConfirmation = $this->authenticate($request);

            if ($authConfirmation['status'] == true) {
                //Reset Rate Limiter
                RateLimiter::clear($this->throttleKey($request));
                //start Auth session
                $request->session()->regenerate();
                //set user locale to app locale
                Session::put('locale', (Auth::user()->locale ?? 'en')); //default languages change
            }
        }

        return $authConfirmation;
    }

    /**
     * Verify that current request user is who he claim to be
     *
     * @param  Request  $request
     * @return bool
     */
    public function validate(Request $request): bool
    {
        if (config('auth.credential_field') != config('constant.login_otp')) {
            $credentials = [];

            if (config('auth.credential_field') == config('constant.login_email')
                || (config('auth.credential_field') == config('constant.login_otp')
                    && config('auth.credential_otp_field') == config('constant.otp_email'))) {
                $credentials['email'] = $request->user()->email;
            } elseif (config('auth.credential_field') == config('constant.login_mobile')
                || (config('auth.credential_field') == config('constant.login_otp')
                    && config('auth.credential_otp_field') == config('constant.otp_mobile'))) {
                $credentials['mobile'] = $request->user()->mobile;
            } elseif (config('auth.credential_field') == config('constant.login_username')) {
                $credentials['username'] = $request->user()->username;
            }

            //Password Field
            $credentials['password'] = $request->password;

            return Auth::guard('web')->validate($credentials);
        } else {
            return true;
        }
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  Request  $request
     * @return array
     */
    public function attemptLogout(Request $request): array
    {
        try {
            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return ['status' => true, 'message' => 'User Logout Successful',
                'level' => config('constant.msg_toastr_success'), 'title' => 'Notification!', ];
        } catch (\Exception $exception) {
            return ['status' => false, 'message' => 'Error: '.$exception->getMessage(),
                'level' => config('constant.msg_toastr_error'), 'title' => 'Error!', ];
        }
    }

    /**
     * Verify is current user is super admin
     *
     * @return bool
     */
    public static function isSuperAdmin(): bool
    {
        if ($authUser = Auth::user()) {
            return $authUser->hasRole(config('constant.super_admin_role'));
        }

        return false;
    }

    /**
     * decided is if user status is disabled
     *
     * @return bool
     */
    public static function isUserEnabled(): bool
    {
        if ($authUser = Auth::user()) {
            return $authUser->enabled == config('constant.enabled_option');
        }

        return false;
    }

    /**
     * if user has to reset password forced
     *
     * @return bool
     */
    public function hasForcePasswordReset(): bool
    {
        if ($authUser = Auth::user()) {
            return (bool) $authUser->force_pass_reset;
        }

        return false;
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @param  LoginRequest  $request
     * @return array
     */
    private function authenticate(LoginRequest $request): array
    {
        //Format config based request value
        $authInfo = $this->formatAuthCredential($request);

        $remember_me = false;

        $confirmation = ['status' => false,
            'message' => __('auth.login.failed'),
            'level' => config('constant.msg_toastr_error'),
            'title' => 'Alert!', ];

        if (config('auth.allow_remembering')) {
            $remember_me = $request->boolean('remember');
        }

        //authentication is OTP
        $confirmation = (! isset($authInfo['password']))
            ? $this->otpBasedLogin($authInfo, $remember_me)
            : $this->credentialBasedLogin($authInfo, $remember_me);

        if ($confirmation['status'] === true) {

            //is user is banned to log in
            if (! self::isUserEnabled()) {

                //logout from all guard
                Auth::logout();
                $confirmation = ['status' => false,
                    'message' => __('auth.login.banned'),
                    'level' => config('constant.msg_toastr_warning'),
                    'title' => 'Alert!', ];
            } elseif ($this->hasForcePasswordReset()) {
                //make this user as guest to reset password
                Auth::logout();

                //create reset token
                $tokenInfo = $this->passwordResetService->createPasswordResetToken($authInfo);

                //reset message
                $confirmation = ['status' => true,
                    'message' => __('auth.login.forced'),
                    'level' => config('constant.msg_toastr_warning'),
                    'title' => 'Notification!',
                    'landing_page' => route('auth.password.reset', $tokenInfo['token']), ];
            } else {
                //set the auth user redirect page
                $confirmation['landing_page'] = route(Auth::user()->home_page ?? config('constant.dashboard_route'));
            }
        }

        return $confirmation;
    }

    /**
     * @param  array  $credential
     * @param  bool  $remember_me
     * @return array
     */
    private function credentialBasedLogin(array $credential, bool $remember_me = false): array
    {
        $confirmation = ['status' => false, 'message' => __('auth.login.failed'), 'level' => config('constant.msg_toastr_error'), 'title' => 'Alert!'];

        if (Auth::attempt($credential, $remember_me)) {
            $confirmation = ['status' => true, 'message' => __('auth.login.success'), 'level' => config('constant.msg_toastr_success'), 'title' => 'Notification'];
        }

        return $confirmation;
    }

    /**
     * @param  array  $credential
     * @param  bool  $remember_me
     * @return array
     */
    private function otpBasedLogin(array $credential, bool $remember_me = false): array
    {
        $confirmation = ['status' => false, 'message' => __('auth.login.failed'), 'level' => config('constant.msg_toastr_error'), 'title' => 'Alert!'];

        if (Auth::attempt($credential, $remember_me)) {
            $confirmation = ['status' => true, 'message' => __('auth.login.success'), 'level' => config('constant.msg_toastr_success'), 'title' => 'Notification'];
        }

        return $confirmation;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @param  LoginRequest  $request
     * @return array
     */
    private function ensureIsNotRateLimited(LoginRequest $request): array
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return ['status' => true, 'message' => __('auth.throttle'), 'level' => config('constant.msg_toastr_warning'), 'title' => 'Warning'];
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        return ['status' => false, 'message' => __('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]), 'level' => config('constant.msg_toastr_warning'), 'title' => 'Warning'];
    }

    /**
     * Get the rate limiting throttle key for the request.
     *
     * @param  LoginRequest  $request
     * @return string
     */
    private function throttleKey(LoginRequest $request): string
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    /**
     * Collect Credential Info from Request based on Config
     *
     * @param  LoginRequest  $request
     * @return array
     */
    private function formatAuthCredential(LoginRequest $request): array
    {
        $credentials = [];

        if (config('auth.credential_field') == config('constant.login_email')
            || (config('auth.credential_field') == config('constant.login_otp')
                && config('auth.credential_otp_field') == config('constant.otp_email'))) {
            $credentials['email'] = $request->email;
        } elseif (config('auth.credential_field') == config('constant.login_mobile')
            || (config('auth.credential_field') == config('constant.login_otp')
                && config('auth.credential_otp_field') == config('constant.otp_mobile'))) {
            $credentials['mobile'] = $request->mobile;
        } elseif (config('auth.credential_field') == config('constant.login_username')) {
            $credentials['username'] = $request->username;
        }

        //Password Field
        if (config('auth.credential_field') != config('constant.login_otp')) {
            $credentials['password'] = $request->password;
        }

        return $credentials;
    }
}
