<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tax_computation')->nullable()->after('payment_methods'); // single, married, parent
            $table->decimal('primary_salary', 10, 2)->default(0)->after('tax_computation');
            $table->boolean('max_ssc_paid')->default(false)->after('primary_salary');
            $table->decimal('estimated_expenses', 10, 2)->default(0)->after('max_ssc_paid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tax_computation', 'primary_salary', 'max_ssc_paid', 'estimated_expenses']);
        });
    }
};