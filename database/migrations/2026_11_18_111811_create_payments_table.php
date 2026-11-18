<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('booking_id')->constrained()->restrictOnDelete();
        $table->string('gateway'); // test, sslcommerz, aamarpay
        $table->string('gateway_txn_id')->nullable();
        $table->unsignedInteger('amount_poysha');
        $table->string('status')->default('PENDING'); // PENDING, SUCCESS, FAILED, REFUNDED
        $table->json('raw_payload')->nullable();
        $table->timestampTz('paid_at')->nullable();
        $table->timestamps();

        // Same gateway transaction duibar process hobe na (idempotency)
        $table->unique(['gateway', 'gateway_txn_id']);
        $table->index(['booking_id', 'status']);
    });
}

public function down(): void
{
    Schema::dropIfExists('payments');
}
};
