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
    Schema::create('turfs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('area_id')->constrained()->restrictOnDelete();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('address');
        $table->decimal('latitude', 10, 7)->nullable();
        $table->decimal('longitude', 10, 7)->nullable();
        $table->string('contact_phone')->nullable();
        $table->json('facilities')->nullable(); // ["parking","washroom","floodlight"]
        $table->string('status')->default('pending'); // pending, active, inactive
        $table->timestamps();
        $table->softDeletes();

        $table->index(['area_id', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turfs');
    }
};
