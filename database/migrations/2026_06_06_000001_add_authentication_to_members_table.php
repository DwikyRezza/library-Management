<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('member_code');
            $table->string('password')->nullable()->after('email');
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'password', 'remember_token']);
        });
    }
};
