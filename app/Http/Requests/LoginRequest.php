<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            // 登入時不驗密碼強度：規則若之後調嚴，舊帳號會登不進來。
            'password' => ['required', 'string'],
        ];
    }
}
