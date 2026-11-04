<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('loan_application_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence')->index();
            $table->string('type', 80)->index();
            $table->json('payload');
            $table->string('previous_hash', 64)->nullable();
            $table->string('hash', 64)->index();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('bigquery_exported_at')->nullable()->index();
            $table->timestamp('gtm_forwarded_at')->nullable()->index();
            $table->timestamps();

            $table->index(['loan_application_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_events');
    }
};
