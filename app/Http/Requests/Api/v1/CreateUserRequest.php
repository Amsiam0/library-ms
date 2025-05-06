<?php

namespace App\Http\Requests\Api\v1;

use App\Http\Requests\Api\CustomFormRequest;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends CustomFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'role' => 'required|string|in:admin,user',
        ];
    }
}
