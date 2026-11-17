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
    Schema::create('pricing_rules', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pitch_id')->constrained()->cascadeOnDelete();
        $table->string('day_type'); // weekday, weekend, all
        $table->time('start_time');
        $table->time('end_time');
        $table->unsignedInteger('price_poysha'); // 1 taka = 100 poysha
        $table->timestamps();

        $table->index(['pitch_id', 'day_type']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
