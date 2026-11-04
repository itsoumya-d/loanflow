<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_deliveries', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider', 40)->index();
            $table->foreignUuid('loan_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 160);
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'idempotency_key', 'attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_deliveries');
    }
};
