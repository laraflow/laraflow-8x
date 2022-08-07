<?php

namespace App\Services\Backend\Auth;

use App\Repositories\Eloquent\Backend\Setting\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laraflow\Core\Services\Utilities\UtilityService;

class PasswordResetService
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @param  UserRepository  $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * create a new token to  reset user password
     *
     * @param  array  $credentials
     * @return array
     */
    public function createPasswordResetToken(array $credentials): array
    {
        if (config('auth.credential_field') === config('constant.login_otp')) {
            return $this->otpBasedPasswordReset($credentials);
        }

        return $this->credentialBasedPasswordReset($credentials);
    }

    /**
     * @param  array  $credentials
     * @return array
     */
    public function updatePassword(array $credentials): array
    {
        $status = Password::reset(
            $credentials,
            function ($user) use ($credentials) {
                $confirmation = $this->userRepository->update([
                    'password' => UtilityService::hashPassword($credentials['password']),
                    'force_pass_reset' => 0,
                    'remember_token' => Str::random(60),
                ], $user->id);
                //event(new PasswordReset($user));
            }
        );

        switch ($status) {
            case Password::PASSWORD_RESET:
                $confirmation = ['status' => true,
                    'message' => __('passwords.reset'),
                    'level' => config('constant.message_success'),
                     ];
                break;

            case Password::RESET_THROTTLED :
                $confirmation = ['status' => false,
                    'message' => __('auth.throttle', ['seconds' => config('auth.passwords.users.throttle')]),
                    'level' => config('constant.message_error'),
                     ];
                break;

            case Password::INVALID_TOKEN:
                $confirmation = ['status' => false,
                    'message' => __('passwords.token'),
                    'level' => config('constant.message_error'),
                     ];
                break;

            default:
                $confirmation = ['status' => false,
                    'message' => __('auth.login.failed'),
                    'level' => config('constant.message_error'),
                     ];
                break;
        }

        return $confirmation;
    }

    /**
     * @param  array  $credentials
     * @return array
     */
    private function credentialBasedPasswordReset(array $credentials): array
    {
        $resetToken = null;

        $status = Password::sendResetLink($credentials, function (User $user, string $token) use (&$resetToken) {
            $resetToken = $token;
        });

        switch ($status) {
            case Password::RESET_LINK_SENT:
                $confirmation = ['status' => true,
                    'message' => __('auth.token', ['minutes' => config('auth.passwords.users.expire')]),
                    'level' => config('constant.message_success'),

                    'token' => $resetToken, ];
                break;

            case Password::RESET_THROTTLED :
                $confirmation = ['status' => false,
                    'message' => __('auth.throttle', ['seconds' => config('auth.passwords.users.throttle')]),
                    'level' => config('constant.message_error'),
                     ];
                break;

            default:
                $confirmation = ['status' => false,
                    'message' => __('auth.login.failed'),
                    'level' => config('constant.message_error'),
                     ];
                break;
        }

        return $confirmation;
    }

    /**
     * @param  array  $credential
     * @return array
     */
    private function otpBasedPasswordReset(array $credential): array
    {
        $confirmation = ['status' => false, 'message' => __('auth.login.failed'), 'level' => config('constant.message_error')];

        if (Auth::attempt($credential)) {
            $confirmation = ['status' => true, 'message' => __('auth.login.success'), 'level' => config('constant.message_success')];
        }

        return $confirmation;
    }
}
