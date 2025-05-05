<?php

namespace App\Http\Requests\Api\v1;

use App\Http\Requests\Api\CustomFormRequest;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookLoanRequest extends CustomFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $is_authenticated = Auth::check();
        $user_role = Auth::user() ? Auth::user()->role : null;
        return $is_authenticated && $user_role === 'user';
    }

    /**;
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => 'required|exists:books,id',
        ];
    }
}
