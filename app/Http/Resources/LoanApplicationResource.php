<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LoanApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoanApplication */
class LoanApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'applicant_name' => $this->applicant_name,
            'email' => $this->email,
            'phone_masked' => $this->maskedPhone(),
            'loan_amount' => round($this->loan_amount_cents / 100, 2),
            'loan_amount_cents' => $this->loan_amount_cents,
            'loan_purpose' => $this->loan_purpose,
            'employment_status' => $this->employment_status,
            'annual_income' => round($this->annual_income_cents / 100, 2),
            'status' => $this->status,
            'source' => $this->source,
            'keap_contact_id' => $this->keap_contact_id,
            'submitted_at' => $this->application()->submitted_at?->toIso8601String(),
            'decision_at' => $this->application()->decision_at?->toIso8601String(),
            'created_at' => $this->application()->created_at->toIso8601String(),
            'updated_at' => $this->application()->updated_at->toIso8601String(),
        ];
    }

    private function application(): LoanApplication
    {
        /** @var LoanApplication $application */
        $application = $this->resource;

        return $application;
    }

    private function maskedPhone(): string
    {
        $phone = (string) $this->phone;

        return mb_strlen($phone) <= 4
            ? '***'
            : str_repeat('*', max(0, mb_strlen($phone) - 4)).mb_substr($phone, -4);
    }
}
