<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dialog_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'replied', 'failed', 'blocked'])->default('pending');
            $table->text('message_text');
            $table->integer('retry_count')->default(0);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('external_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['mailing_id', 'status']);
            $table->index(['client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_messages');
    }
};
