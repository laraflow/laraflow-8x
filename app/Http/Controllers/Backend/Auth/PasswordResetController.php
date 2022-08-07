<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Auth\NewPasswordRequest;
use App\Http\Requests\Backend\Auth\PasswordResetRequest;
use App\Services\Backend\Auth\PasswordResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    /**
     * @var PasswordResetService
     */
    private $passwordResetService;

    /**
     * @param  PasswordResetService  $passwordResetService
     */
    public function __construct(PasswordResetService $passwordResetService)
    {
        $this->passwordResetService = $passwordResetService;
    }

    /**
     * Display the password reset link request view.
     *
     * @return View
     */
    public function _invoke(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  PasswordResetRequest  $request
     * @return RedirectResponse
     */
    public function forgot(PasswordResetRequest $request): RedirectResponse
    {
        $inputs = $request->only('email', 'mobile', 'username');

        $confirm = $this->passwordResetService->createPasswordResetToken($inputs);

        if ($confirm['status'] === true) {
            flasher($confirm['message'], $confirm['level']);

            return redirect()->to(route('auth.password.reset', $confirm['token']));
        }

        flasher($confirm['message'], $confirm['level']);

        return redirect()->back();
    }

    public function token($token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function reset(NewPasswordRequest $request): RedirectResponse
    {
        $inputs = $request->only('email', 'mobile', 'username', 'password', 'password_confirmation', 'token');

        $confirm = $this->passwordResetService->updatePassword($inputs);

        if ($confirm['status'] === true) {
            flasher($confirm['message'], $confirm['level']);

            return redirect()->to(route('auth.login'));
        }

        flasher($confirm['message'], $confirm['level']);

        return redirect()->back();
    }
}
