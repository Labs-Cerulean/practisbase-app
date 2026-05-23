<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Self-referencing foreign key to link a Credit Note to its parent Invoice
            $table->foreignId('parent_document_id')->nullable()->constrained('invoices')->nullOnDelete()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['parent_document_id']);
            $table->dropColumn('parent_document_id');
        });
    }
};