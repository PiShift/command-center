<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'    => 'required|exists:customers,id',
            'project_id'     => 'nullable|exists:projects,id',
            'issue_date'     => 'required|date',
            'due_date'       => 'required|date|after_or_equal:issue_date',
            'currency'       => 'nullable|string|max:10',
            'exchange_rate'  => 'nullable|numeric|min:0.0001',
            'discount_type'  => 'nullable|in:percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate'       => 'nullable|numeric|min:0|max:100',
            'notes'          => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $projectId  = $this->input('project_id');
            $customerId = $this->input('customer_id');
            if ($projectId && $customerId) {
                $belongs = \App\Models\Project::where('id', $projectId)
                    ->where('customer_id', $customerId)->exists();
                if (!$belongs) {
                    $v->errors()->add('project_id', 'Project must belong to the selected customer.');
                }
            }
        });
    }
}
