<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('digital_loans', function (Blueprint $table): void {
            $table->unsignedInteger('last_read_page')->default(1)->after('due_at');
        });
    }

    public function down(): void
    {
        Schema::table('digital_loans', function (Blueprint $table): void {
            $table->dropColumn('last_read_page');
        });
    }
};
