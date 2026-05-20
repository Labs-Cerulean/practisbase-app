<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The Professional
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); // The Client
            $table->string('invoice_number'); // e.g., INV-0001
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_total', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('unpaid'); // unpaid, paid, overdue, cancelled
            $table->json('items'); // JSON array for dynamic rows
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};