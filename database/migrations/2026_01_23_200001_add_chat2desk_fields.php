<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Добавляем chat2desk_id в таблицу clients
        Schema::table('clients', function (Blueprint $table) {
            $table->string('chat2desk_id')->nullable()->after('phone')->index();
        });

        // Добавляем external_id и closed_at в таблицу dialogs
        Schema::table('dialogs', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('id')->index();
            $table->timestamp('closed_at')->nullable()->after('last_message_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('chat2desk_id');
        });

        Schema::table('dialogs', function (Blueprint $table) {
            $table->dropColumn(['external_id', 'closed_at']);
        });
    }
};
