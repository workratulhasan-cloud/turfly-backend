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
    Schema::create('owner_profiles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
        $table->string('business_name');
        $table->string('nid_number')->nullable();
        $table->string('trade_license_number')->nullable();
        $table->string('verification_status')->default('pending'); // pending, approved, rejected
        $table->text('rejection_reason')->nullable();
        $table->timestamp('verified_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owner_profiles');
    }
};
