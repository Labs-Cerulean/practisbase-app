<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->integer('year'); // e.g., 2026
            $table->string('type'); // e.g., 'income_single', 'income_married', 'income_parent', 'ssc_pt', 'ssc_ft', 'ta22'
            $table->json('rates_json'); // The actual brackets, percentages, and caps
            $table->timestamps();

            // A user can only have one set of rules per type per year
            $table->unique(['year', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};