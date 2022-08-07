<?php

namespace App\Http\Requests\Backend\Auth;

use App\Rules\PhoneNumber;
use App\Rules\Username;
use App\Supports\Constant;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [];

        //Credential Field
        if (config('auth.credential_field') == config('constant.login_email')
            || (config('auth.credential_field') == config('constant.login_otp')
                && config('auth.credential_otp_field') == config('constant.otp_email'))) {
            $rules['email'] = 'required|min:10|max:255|string|email';
        } elseif (config('auth.credential_field') == config('constant.login_mobile')
            || (config('auth.credential_field') == config('constant.login_otp')
                && config('auth.credential_otp_field') == config('constant.otp_mobile'))) {
            $rules['mobile'] = ['required', 'string', 'min:11', 'max:11', new PhoneNumber];
        } elseif (config('auth.credential_field') == config('constant.login_username')) {
            $rules['username'] = ['required', new Username, 'min:5', 'max:255', 'string'];
        }

        //Password Field
        if (config('auth.credential_field') != config('constant.login_otp')) {
            $rules['password'] = ['required', 'min:'.config('auth.minimum_password_length'), 'max:255', 'string'];
        }

        return $rules;
    }
}
