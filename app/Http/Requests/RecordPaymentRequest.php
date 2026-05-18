<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'required|in:bank_transfer,cash,check,card,other',
            'reference'    => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'proof'        => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
}
