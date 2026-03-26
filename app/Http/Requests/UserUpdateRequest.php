<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    use ProfileValidationRules;
    use PasswordValidationRules;

    public function rules(): array
    {
        return array_merge($this->profileRules($this->route('user')->id), [
            'password' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rules\Password::default(),
                'confirmed',
            ],
        ]);
    }
}
