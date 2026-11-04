<?php

declare(strict_types=1);

namespace Tests\WordPress;

use Brain\Monkey\Functions;
use Loanflow_Form;

class FormTest extends WordPressTestCase
{
    public function test_it_sanitizes_and_converts_amounts_to_cents(): void
    {
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('sanitize_email')->returnArg();
        Functions\when('sanitize_key')->returnArg();
        Functions\when('sanitize_textarea_field')->returnArg();

        $form = new Loanflow_Form;
        $data = $form->sanitize([
            'applicant_name' => ' Jane Doe ',
            'email' => 'jane@example.com',
            'phone' => '+64211234567',
            'loan_amount' => '25000',
            'loan_purpose' => 'vehicle',
            'employment_status' => 'employed',
            'annual_income' => '95000',
            'credit_check_consent' => '1',
            'privacy_consent' => '1',
            'notes' => 'First car.',
        ]);

        $this->assertSame(2_500_000, $data['loan_amount_cents']);
        $this->assertSame(9_500_000, $data['annual_income_cents']);
        $this->assertTrue($data['credit_check_consent']);
        $this->assertSame('First car.', $data['notes']);
    }

    public function test_it_validates_a_complete_application(): void
    {
        Functions\when('is_email')->alias(static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false);

        $form = new Loanflow_Form;
        $errors = $form->validate([
            'applicant_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+64211234567',
            'loan_amount_cents' => 2_500_000,
            'loan_purpose' => 'vehicle',
            'employment_status' => 'employed',
            'annual_income_cents' => 9_500_000,
            'credit_check_consent' => true,
            'privacy_consent' => true,
            'website' => '',
        ]);

        $this->assertSame([], $errors);
    }

    public function test_it_rejects_missing_consent_and_invalid_values(): void
    {
        Functions\when('is_email')->justReturn(false);

        $form = new Loanflow_Form;
        $errors = $form->validate([
            'applicant_name' => '',
            'email' => 'bad',
            'phone' => 'abc',
            'loan_amount_cents' => 1,
            'loan_purpose' => 'crypto',
            'employment_status' => 'unknown',
            'annual_income_cents' => -1,
            'credit_check_consent' => false,
            'privacy_consent' => false,
            'website' => 'spam',
        ]);

        $this->assertArrayHasKey('applicant_name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('loan_amount', $errors);
        $this->assertArrayHasKey('loan_purpose', $errors);
        $this->assertArrayHasKey('employment_status', $errors);
        $this->assertArrayHasKey('credit_check_consent', $errors);
        $this->assertArrayHasKey('privacy_consent', $errors);
        $this->assertArrayHasKey('form', $errors);
    }

    public function test_the_shortcode_renders_an_accessible_form(): void
    {
        Functions\when('shortcode_atts')->alias(static function (array $defaults, array $atts): array {
            return array_merge($defaults, $atts);
        });
        Functions\expect('wp_nonce_field')->once()->with('loanflow_form', 'loanflow_nonce');

        $form = new Loanflow_Form;
        $html = $form->shortcode([]);

        $this->assertStringContainsString('data-loanflow-form', $html);
        $this->assertStringContainsString('name="applicant_name"', $html);
        $this->assertStringContainsString('name="credit_check_consent"', $html);
        $this->assertStringContainsString('data-loanflow-status', $html);
    }
}
