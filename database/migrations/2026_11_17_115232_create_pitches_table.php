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
    Schema::create('pitches', function (Blueprint $table) {
        $table->id();
        $table->foreignId('turf_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->string('sport_type'); // football, cricket
        $table->string('size')->nullable(); // 5v5, 7v7, 9v9
        $table->unsignedSmallInteger('slot_minutes')->default(60);
        $table->string('status')->default('active'); // active, inactive
        $table->timestamps();
        $table->softDeletes();

        $table->index(['turf_id', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitches');
    }
};
