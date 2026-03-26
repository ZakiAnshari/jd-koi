<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    use ProfileValidationRules;
    use PasswordValidationRules;

    public function rules(): array
    {
        return array_merge($this->profileRules(), [
            'password' => $this->passwordRules(),
        ]);
    }
}
