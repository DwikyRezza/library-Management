<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_book_assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('book_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('original_path');
            $table->string('pages_path')->nullable();
            $table->string('original_name');
            $table->string('mime_type')->default('application/pdf');
            $table->unsignedBigInteger('file_size');
            $table->string('sha256', 64);
            $table->unsignedInteger('page_count')->default(0);
            $table->string('status')->index();
            $table->text('last_error')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('rendered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reading_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('digital_book_asset_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at')->index();
            $table->timestamp('last_active_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('last_page')->default(1);
            $table->unsignedInteger('max_page')->default(1);
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'last_active_at']);
            $table->index(['book_id', 'last_active_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_sessions');
        Schema::dropIfExists('digital_book_assets');
    }
};
