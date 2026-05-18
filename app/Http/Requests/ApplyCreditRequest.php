<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCreditRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'credit_id' => 'required|exists:customer_credits,id',
            'amount'    => 'required|numeric|min:0.01',
        ];
    }
}
