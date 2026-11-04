<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoanApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoanApplication>
 */
class LoanApplicationFactory extends Factory
{
    protected $model = LoanApplication::class;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'reference' => 'LF-'.now()->format('Y').'-'.Str::upper(Str::random(8)),
            'applicant_name' => fake()->name(),
            'email' => $email,
            'email_hash' => hash('sha256', mb_strtolower($email)),
            'phone' => '+6421'.fake()->numerify('#######'),
            'loan_amount_cents' => fake()->numberBetween(1_000, 50_000) * 100,
            'loan_purpose' => fake()->randomElement(['vehicle', 'home_improvement', 'debt_consolidation', 'business']),
            'employment_status' => fake()->randomElement(['employed', 'self_employed', 'contractor', 'retired']),
            'annual_income_cents' => fake()->numberBetween(45_000, 180_000) * 100,
            'credit_check_consent' => true,
            'privacy_consent' => true,
            'status' => LoanApplication::STATUS_RECEIVED,
            'source' => 'test',
            'submitted_at' => now(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => LoanApplication::STATUS_APPROVED,
            'decision_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'status' => LoanApplication::STATUS_DECLINED,
            'decision_at' => now(),
        ]);
    }

    public function referred(): static
    {
        return $this->state(fn (): array => [
            'status' => LoanApplication::STATUS_REFERRED,
        ]);
    }
}
