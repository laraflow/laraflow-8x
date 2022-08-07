<?php

use App\Http\Controllers\Backend\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Backend\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Backend\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Backend\Auth\LoginController;
use App\Http\Controllers\Backend\Auth\PasswordResetController;
use App\Http\Controllers\Backend\Auth\RegisterController;
use App\Http\Controllers\Backend\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix(config('auth.admin_auth_prefix'))
    ->name('backend.')
    ->group(function () {
        /**
         * Authentication Route
         */
        Route::name('auth.')->group(function () {
            Route::view('/privacy-terms', 'auth::terms')->name('terms');

            Route::get('/login', LoginController::class)
                ->middleware('guest')
                ->name('login');

            Route::post('/login', [LoginController::class, 'login'])
                ->middleware('guest');

            Route::post('/logout', [LoginController::class, 'logout'])
                ->middleware('auth')
                ->name('logout');

            if (config('auth.allow_register')) {
                Route::get('/register', [RegisterController::class, 'create'])
                    ->middleware('guest')
                    ->name('register');

                Route::post('/register', [RegisterController::class, 'store'])
                    ->middleware('guest');
            }

            if (config('auth.allow_password_reset')) {
                Route::get('/forgot-password', PasswordResetController::class)
                    ->middleware('guest')
                    ->name('password.request');
            }

            Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])
                ->middleware('guest')
                ->name('password.forgot');

            Route::get('/reset-password/{token}', [PasswordResetController::class, 'token'])
                ->middleware('guest')
                ->name('password.token');

            Route::post('/reset-password', [PasswordResetController::class, 'reset'])
                ->name('password.reset');

            Route::get('/verify-email', [EmailVerificationPromptController::class, '__invoke'])
                ->middleware('auth')
                ->name('verification.notice');

            Route::get('/verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                ->middleware(['auth', 'signed', 'throttle:6,1'])
                ->name('verification.verify');

            Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware(['auth', 'throttle:6,1'])
                ->name('verification.send');

            Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->middleware('auth')
                ->name('password.confirm');

            Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store'])
                ->middleware('auth');
        });
    });
