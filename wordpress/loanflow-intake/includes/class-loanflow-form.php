<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

final class Loanflow_Form
{
    /**
     * @param  array<string, mixed>  $atts
     */
    public function shortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'title' => Loanflow_I18n::t('Apply for a loan'),
            'button' => Loanflow_I18n::t('Submit application'),
        ], $atts, 'loanflow_application_form');

        ob_start();
        ?>
        <form class="loanflow-form" data-loanflow-form novalidate>
            <h2 class="loanflow-form__title"><?php echo Loanflow_I18n::esc((string) $atts['title']); ?></h2>
            <p class="loanflow-form__intro">
                <?php echo Loanflow_I18n::t('Takes about two minutes. We will only use your details to assess this application.'); ?>
            </p>

            <div class="loanflow-form__status" role="status" aria-live="polite" data-loanflow-status></div>

            <div class="loanflow-form__grid">
                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Full name'); ?></span>
                    <input type="text" name="applicant_name" autocomplete="name" required maxlength="160">
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Email'); ?></span>
                    <input type="email" name="email" autocomplete="email" required maxlength="254">
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Phone'); ?></span>
                    <input type="tel" name="phone" autocomplete="tel" required maxlength="32">
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Loan amount (NZD)'); ?></span>
                    <input type="number" name="loan_amount" min="1000" max="2000000" step="100" inputmode="numeric" required>
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Loan purpose'); ?></span>
                    <select name="loan_purpose" required>
                        <option value=""><?php echo Loanflow_I18n::t('Select a purpose'); ?></option>
                        <option value="vehicle"><?php echo Loanflow_I18n::t('Vehicle'); ?></option>
                        <option value="home_improvement"><?php echo Loanflow_I18n::t('Home improvement'); ?></option>
                        <option value="debt_consolidation"><?php echo Loanflow_I18n::t('Debt consolidation'); ?></option>
                        <option value="business"><?php echo Loanflow_I18n::t('Business'); ?></option>
                        <option value="education"><?php echo Loanflow_I18n::t('Education'); ?></option>
                        <option value="medical"><?php echo Loanflow_I18n::t('Medical'); ?></option>
                        <option value="other"><?php echo Loanflow_I18n::t('Other'); ?></option>
                    </select>
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Employment status'); ?></span>
                    <select name="employment_status" required>
                        <option value=""><?php echo Loanflow_I18n::t('Select status'); ?></option>
                        <option value="employed"><?php echo Loanflow_I18n::t('Employed'); ?></option>
                        <option value="self_employed"><?php echo Loanflow_I18n::t('Self employed'); ?></option>
                        <option value="contractor"><?php echo Loanflow_I18n::t('Contractor'); ?></option>
                        <option value="retired"><?php echo Loanflow_I18n::t('Retired'); ?></option>
                        <option value="student"><?php echo Loanflow_I18n::t('Student'); ?></option>
                        <option value="unemployed"><?php echo Loanflow_I18n::t('Unemployed'); ?></option>
                    </select>
                </label>

                <label class="loanflow-field">
                    <span><?php echo Loanflow_I18n::t('Annual income (NZD)'); ?></span>
                    <input type="number" name="annual_income" min="0" max="10000000" step="100" inputmode="numeric" required>
                </label>

                <label class="loanflow-field loanflow-field--full">
                    <span><?php echo Loanflow_I18n::t('Anything we should know? (optional)'); ?></span>
                    <textarea name="notes" rows="3" maxlength="1000"></textarea>
                </label>
            </div>

            <div class="loanflow-form__consent">
                <label>
                    <input type="checkbox" name="credit_check_consent" value="1" required>
                    <?php echo Loanflow_I18n::t('I consent to a credit check for this application.'); ?>
                </label>
                <label>
                    <input type="checkbox" name="privacy_consent" value="1" required>
                    <?php echo Loanflow_I18n::t('I have read and accept the privacy statement.'); ?>
                </label>
            </div>

            <div class="loanflow-form__honeypot" aria-hidden="true">
                <label>
                    <span><?php echo Loanflow_I18n::t('Leave this field empty'); ?></span>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <?php wp_nonce_field('loanflow_form', 'loanflow_nonce'); ?>

            <button type="submit" class="loanflow-form__submit">
                <?php echo Loanflow_I18n::esc((string) $atts['button']); ?>
            </button>
        </form>
        <?php

        return (string) ob_get_clean();
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function sanitize(array $raw): array
    {
        $amount = isset($raw['loan_amount']) ? (float) $raw['loan_amount'] : 0.0;
        $income = isset($raw['annual_income']) ? (float) $raw['annual_income'] : 0.0;

        return [
            'applicant_name' => sanitize_text_field((string) ($raw['applicant_name'] ?? '')),
            'email' => sanitize_email((string) ($raw['email'] ?? '')),
            'phone' => sanitize_text_field((string) ($raw['phone'] ?? '')),
            'loan_amount_cents' => (int) round($amount * 100),
            'loan_purpose' => sanitize_key((string) ($raw['loan_purpose'] ?? '')),
            'employment_status' => sanitize_key((string) ($raw['employment_status'] ?? '')),
            'annual_income_cents' => (int) round($income * 100),
            'credit_check_consent' => ! empty($raw['credit_check_consent']),
            'privacy_consent' => ! empty($raw['privacy_consent']),
            'notes' => sanitize_textarea_field((string) ($raw['notes'] ?? '')),
            'website' => sanitize_text_field((string) ($raw['website'] ?? '')),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        if (trim((string) ($data['applicant_name'] ?? '')) === '') {
            $errors['applicant_name'] = Loanflow_I18n::t('Please enter your full name.');
        }

        if (! is_email((string) ($data['email'] ?? ''))) {
            $errors['email'] = Loanflow_I18n::t('Please enter a valid email address.');
        }

        if (preg_match('/^\+?[0-9 ()-]{7,32}$/', (string) ($data['phone'] ?? '')) !== 1) {
            $errors['phone'] = Loanflow_I18n::t('Please enter a valid phone number.');
        }

        $amount = (int) ($data['loan_amount_cents'] ?? 0);

        if ($amount < 100_000 || $amount > 200_000_000) {
            $errors['loan_amount'] = Loanflow_I18n::t('Loan amount must be between NZD 1,000 and NZD 2,000,000.');
        }

        $purposes = ['vehicle', 'home_improvement', 'debt_consolidation', 'business', 'education', 'medical', 'other'];

        if (! in_array((string) ($data['loan_purpose'] ?? ''), $purposes, true)) {
            $errors['loan_purpose'] = Loanflow_I18n::t('Please choose a loan purpose.');
        }

        $employment = ['employed', 'self_employed', 'contractor', 'retired', 'student', 'unemployed'];

        if (! in_array((string) ($data['employment_status'] ?? ''), $employment, true)) {
            $errors['employment_status'] = Loanflow_I18n::t('Please choose your employment status.');
        }

        if ((int) ($data['annual_income_cents'] ?? -1) < 0) {
            $errors['annual_income'] = Loanflow_I18n::t('Please enter your annual income.');
        }

        if (empty($data['credit_check_consent'])) {
            $errors['credit_check_consent'] = Loanflow_I18n::t('Credit check consent is required.');
        }

        if (empty($data['privacy_consent'])) {
            $errors['privacy_consent'] = Loanflow_I18n::t('Privacy consent is required.');
        }

        if (! empty($data['website'])) {
            $errors['form'] = Loanflow_I18n::t('Submission rejected.');
        }

        return $errors;
    }
}
