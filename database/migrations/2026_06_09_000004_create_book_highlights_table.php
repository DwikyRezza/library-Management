<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('digital_loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('page_number');
            $table->text('highlighted_text');
            $table->string('color', 7);
            $table->longText('serialized_range');
            $table->timestamps();

            $table->index(['digital_loan_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_highlights');
    }
};
