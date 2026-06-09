<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->foreignId('book_copy_id')->constrained()->restrictOnDelete();
            $table->timestamp('borrowed_at')->index();
            $table->timestamp('due_at')->index();
            $table->timestamp('extended_at')->nullable();
            $table->timestamp('returned_at')->nullable()->index();
            $table->string('return_reason')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'returned_at']);
            $table->index(['book_copy_id', 'returned_at']);
        });

        Schema::create('booklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booklists');
        Schema::dropIfExists('digital_loans');
    }
};
