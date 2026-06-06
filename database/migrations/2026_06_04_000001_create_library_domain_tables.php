<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('member_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('max_books');
            $table->unsignedInteger('loan_days')->default(14);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->string('author')->index();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('isbn')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('book_categories')->restrictOnDelete();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('total_copies')->default(0);
            $table->unsignedInteger('available_copies')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'title']);
        });

        Schema::create('book_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('copy_code')->unique();
            $table->string('shelf_location')->nullable();
            $table->string('status')->default('available')->index();
            $table->text('condition_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['book_id', 'status']);
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_code')->unique();
            $table->string('first_name')->index();
            $table->string('last_name')->index();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('roll_number')->unique();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->foreignId('member_category_id')->constrained()->restrictOnDelete();
            $table->boolean('approved')->default(false)->index();
            $table->boolean('rejected')->default(false)->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedInteger('books_borrowed_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approved', 'rejected']);
            $table->index(['member_category_id', 'approved']);
        });

        Schema::create('borrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('book_copy_id')->constrained()->restrictOnDelete();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->index();
            $table->timestamp('due_at')->index();
            $table->timestamp('returned_at')->nullable()->index();
            $table->string('status')->default('borrowed')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['book_copy_id', 'status']);
            $table->index(['status', 'due_at']);
        });

        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement(
                "CREATE UNIQUE INDEX borrow_transactions_active_copy_unique ON borrow_transactions (book_copy_id) WHERE returned_at IS NULL AND status IN ('borrowed', 'overdue')"
            );
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX IF EXISTS borrow_transactions_active_copy_unique');
        }

        Schema::dropIfExists('borrow_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('book_copies');
        Schema::dropIfExists('books');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('member_categories');
        Schema::dropIfExists('book_categories');
    }
};
