<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Auth\LoginRequest;
use App\Services\Backend\Auth\AuthenticatedSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Class LoginController
 * @package App\Http\Controllers\Backend\Auth
 */
class LoginController extends Controller
{
    /**
     * @var AuthenticatedSessionService
     */
    private $authenticatedSessionService;

    /**
     * @param AuthenticatedSessionService $authenticatedSessionService
     */
    public function __construct(AuthenticatedSessionService $authenticatedSessionService)
    {
        $this->authenticatedSessionService = $authenticatedSessionService;
    }

    /**
     * Display the login view.
     *
     * @return View
     */
    public function __invoke(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming auth request.
     *
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $confirm = $this->authenticatedSessionService->attemptLogin($request);

        if ($confirm['status'] === true) {

            flasher($confirm['message'], $confirm['level']);

            return redirect()->to($confirm['landing_page']);
        }

        flasher($confirm['message'], $confirm['level']);

        return redirect()->back();
    }

    /**
     * Destroy an authenticated session.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $confirm = $this->authenticatedSessionService->attemptLogout($request);
        if ($confirm['status'] === true) {
            flasher($confirm['message'], $confirm['level']);

            return redirect()->to(route('home'));
        }

        flasher($confirm['message'], $confirm['level']);

        return redirect()->back();
    }
}
