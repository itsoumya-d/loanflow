<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('reference', 32)->unique();
            $table->string('applicant_name', 160);
            $table->string('email', 254);
            $table->string('email_hash', 64)->index();
            $table->text('phone');
            $table->unsignedBigInteger('loan_amount_cents');
            $table->string('loan_purpose', 64);
            $table->string('employment_status', 64);
            $table->unsignedBigInteger('annual_income_cents');
            $table->boolean('credit_check_consent')->default(false);
            $table->boolean('privacy_consent')->default(false);
            $table->string('status', 32)->default('received')->index();
            $table->string('source', 32)->default('api')->index();
            $table->string('idempotency_key', 128)->nullable()->unique();
            $table->string('payload_hash', 64)->nullable();
            $table->string('keap_contact_id', 64)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamp('decision_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
