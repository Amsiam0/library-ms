<?php

namespace App\Http\Requests\Api\v1;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Book::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'ebook' => 'nullable|string',
            'has_physical' => 'required|boolean',
            'thumbnail' => 'nullable|string',
            'quantity' => 'required_if:has_physical,true|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required_if' => 'The quantity field is required when physical book is selected.',
            'quantity.min' => 'The quantity must be at least 1 for physical books.',
        ];
    }
}
