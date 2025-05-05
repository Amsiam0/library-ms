<?php

namespace App\Http\Requests\Api\v1;

use App\Http\Requests\Api\CustomFormRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class FeedbackRequest extends CustomFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ];
    }
}
