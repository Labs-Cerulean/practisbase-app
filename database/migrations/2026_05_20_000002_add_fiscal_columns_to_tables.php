<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade the Users Table
        Schema::table('users', function (Blueprint $table) {
            $table->string('employment_type')->default('full_time')->after('profession'); // full_time or part_time
            $table->date('date_of_birth')->nullable()->after('employment_type');
            $table->string('vat_status')->default('exempt')->after('date_of_birth'); // exempt, article_11, article_10
            $table->string('vat_number')->nullable()->after('vat_status');
        });

        // 2. Upgrade the Invoices (Document) Table
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type')->default('invoice')->after('status'); // invoice, rfp, credit_note
            $table->foreignId('linked_document_id')->nullable()->after('type')->constrained('invoices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['employment_type', 'date_of_birth', 'vat_status', 'vat_number']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['linked_document_id']);
            $table->dropColumn(['type', 'linked_document_id']);
        });
    }
};