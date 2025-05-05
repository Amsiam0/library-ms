<?php

namespace App\Http\Requests\Api\v1;

use App\Http\Requests\Api\CustomFormRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RequestUpdateDueDateRequest extends CustomFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->role === 'user';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'dueDate' => 'required|date_format:Y-m-d',
            'reason' => 'required|string|max:255',
        ];
    }

    //transform the dueDate to a date object
    protected function prepareForValidation()
    {
        $this->merge([
            'due_date' => $this->dueDate,
        ]);
    }
}
