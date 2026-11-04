<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanApplicationRequest extends FormRequest
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
            'applicant_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9 ()-]{7,32}$/'],
            'loan_amount_cents' => [
                'required',
                'integer',
                'min:'.(int) config('loanflow.limits.min_loan_amount_cents'),
                'max:'.(int) config('loanflow.limits.max_loan_amount_cents'),
            ],
            'loan_purpose' => ['required', 'in:vehicle,home_improvement,debt_consolidation,business,education,medical,other'],
            'employment_status' => ['required', 'in:employed,self_employed,contractor,retired,student,unemployed'],
            'annual_income_cents' => [
                'required',
                'integer',
                'min:0',
                'max:'.(int) config('loanflow.limits.max_annual_income_cents'),
            ],
            'credit_check_consent' => ['required', 'accepted'],
            'privacy_consent' => ['required', 'accepted'],
            'metadata' => ['sometimes', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }

        foreach (['applicant_name', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}
