<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->json('data');
            $table->timestamps();

            $table->unique(['member_id', 'book_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_annotations');
    }
};
