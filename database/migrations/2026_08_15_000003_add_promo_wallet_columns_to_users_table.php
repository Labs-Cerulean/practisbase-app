<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'credit_balance')) {
                $table->decimal('credit_balance', 10, 2)->default(0)->after('referral_code');
            }
            if (! Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('credit_balance');
            }
            if (! Schema::hasColumn('users', 'applied_promotion_id')) {
                $table->foreignId('applied_promotion_id')->nullable()->after('trial_ends_at')
                    ->constrained('promotions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'applied_promotion_id')) {
                $table->dropConstrainedForeignId('applied_promotion_id');
            }
            if (Schema::hasColumn('users', 'trial_ends_at')) {
                $table->dropColumn('trial_ends_at');
            }
            if (Schema::hasColumn('users', 'credit_balance')) {
                $table->dropColumn('credit_balance');
            }
        });
    }
};
