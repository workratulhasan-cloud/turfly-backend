<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('bookings', function (Blueprint $table) {
        $table->id();
        $table->string('reference')->unique(); // e.g. TRF-20261117-AB12
        $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
        $table->foreignId('pitch_id')->constrained()->restrictOnDelete();
        $table->timestampTz('start_at');
        $table->timestampTz('end_at');
        $table->string('status')->default('PENDING');
        // PENDING, CONFIRMED, COMPLETED, EXPIRED, CANCELLED, REFUNDED, NO_SHOW
        $table->string('source')->default('app'); // app, manual
        $table->unsignedInteger('total_amount_poysha');
        $table->unsignedInteger('paid_amount_poysha')->default(0);
        $table->timestampTz('expires_at')->nullable(); // PENDING hold er shomoy
        $table->string('customer_name')->nullable();  // manual booking er jonno
        $table->string('customer_phone')->nullable(); // manual booking er jonno
        $table->text('notes')->nullable();
        $table->timestampTz('cancelled_at')->nullable();
        $table->timestamps();

        $table->index(['pitch_id', 'start_at']);
        $table->index(['user_id', 'status']);
        $table->index(['status', 'expires_at']);
    });

    // Double booking rokhar asol shurokkha: database level constraint
    DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

    DB::statement("
        ALTER TABLE bookings
        ADD CONSTRAINT bookings_no_overlap
        EXCLUDE USING gist (
            pitch_id WITH =,
            tstzrange(start_at, end_at) WITH &&
        ) WHERE (status IN ('PENDING', 'CONFIRMED'))
    ");

    DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_end_after_start CHECK (end_at > start_at)');
}

public function down(): void
{
    Schema::dropIfExists('bookings');
}
};
