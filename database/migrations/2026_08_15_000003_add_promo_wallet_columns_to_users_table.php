<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'credit_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('credit_balance', 10, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'applied_promotion_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('applied_promotion_id')->nullable()
                    ->constrained('promotions')->nullOnDelete();
            });

            return;
        }

        if (! $this->hasConstraint('users_applied_promotion_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('applied_promotion_id')
                    ->references('id')
                    ->on('promotions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'applied_promotion_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('applied_promotion_id');
            });
        }

        if (Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('trial_ends_at');
            });
        }

        if (Schema::hasColumn('users', 'credit_balance')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('credit_balance');
            });
        }
    }

    private function hasConstraint(string $name): bool
    {
        $row = DB::selectOne(
            'select 1 as present from information_schema.table_constraints where constraint_name = ? and table_name = ? limit 1',
            [$name, 'users']
        );

        return $row !== null;
    }
};
