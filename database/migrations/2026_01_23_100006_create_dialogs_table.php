<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['whatsapp', 'telegram', 'sms'])->default('whatsapp');
            $table->string('external_chat_id')->nullable();
            $table->enum('current_status', [
                'new', 'ai_connected', 'presentation', 'communication',
                'questions', 'objections', 'reschedule', 'ready_to_buy',
                'data_provided', 'partial_data', 'no_data', 'manager_requested',
                'strong_negative', 'purchased', 'refused', 'cross_sell',
                'consultation_requested', 'no_response', 'completed'
            ])->default('new');
            $table->boolean('is_ai_active')->default(false);
            $table->integer('messages_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_client_message_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'current_status']);
            $table->index('external_chat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialogs');
    }
};
