<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_receipts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('provider', 40)->index();
            $table->string('event_id', 120)->nullable()->index();
            $table->string('signature_hash', 128)->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->string('payload_hash', 64);
            $table->string('status', 32)->default('received')->index();
            $table->text('error')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_receipts');
    }
};
