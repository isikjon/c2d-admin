<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('patronymic')->nullable()->after('last_name');
            $table->string('city')->nullable();
            $table->string('country')->default('Россия');
            $table->enum('status', ['active', 'blocked'])->default('active');
            $table->boolean('two_factor_verified')->default(false);
            $table->timestamp('two_factor_verified_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn([
                'organization_id', 'phone', 'first_name', 'last_name',
                'patronymic', 'city', 'country', 'status',
                'two_factor_verified', 'two_factor_verified_at', 'deleted_at'
            ]);
        });
    }
};
