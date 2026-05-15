<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Professional Data
            $table->string('profession')->nullable();
            $table->string('warrant_type')->nullable();
            $table->string('warrant_number')->nullable();
            
            // 2. Billing State
            $table->string('tier')->default('free'); // Everyone starts as free
            
            // 3. Referral Engine
            $table->string('referral_code')->unique()->nullable();
            $table->foreignId('referred_by_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn([
                'profession', 
                'warrant_type', 
                'warrant_number', 
                'tier', 
                'referral_code', 
                'referred_by_id'
            ]);
        });
    }
};