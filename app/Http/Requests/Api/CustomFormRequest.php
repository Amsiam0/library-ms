<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CustomFormRequest extends FormRequest
{


    protected function failedAuthorization()
    {
        response()->json([
            'error' => 'Unauthorized',
            'message' => 'You are not authorized to perform this action.'
        ], 403)->send();
        exit;
    }
}
