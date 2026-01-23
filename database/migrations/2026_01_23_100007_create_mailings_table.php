<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('step_order')->default(1);
            $table->text('message_text');
            $table->enum('channel', ['whatsapp', 'telegram', 'sms'])->default('whatsapp');
            $table->integer('delay_minutes')->default(0);
            $table->string('trigger_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['campaign_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailings');
    }
};
