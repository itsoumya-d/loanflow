<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return true;
        }

        return $this->user()->can('manage-loan-applications');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:screening,approved,declined,referred'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
