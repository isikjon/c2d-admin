<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_product_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dialog_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->boolean('is_complete')->default(false);
            $table->timestamps();
            $table->index(['client_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_product_data');
    }
};
