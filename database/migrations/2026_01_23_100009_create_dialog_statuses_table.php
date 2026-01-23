<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialog_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialog_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('previous_status')->nullable();
            $table->string('source')->default('system');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['dialog_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialog_statuses');
    }
};
