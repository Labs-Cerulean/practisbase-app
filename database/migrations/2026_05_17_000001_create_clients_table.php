<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            // Links the client strictly to the professional who created them
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); 
            
            // The Universal Core
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            
            // The Smart JSON Container for Profession-Specific Data
            $table->jsonb('profile_data')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};