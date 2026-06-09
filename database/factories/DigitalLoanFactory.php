<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\DigitalLoan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalLoan>
 */
class DigitalLoanFactory extends Factory
{
    public function definition(): array
    {
        $borrowedAt = now()->subDay();

        return [
            'member_id' => Member::factory(),
            'book_id' => Book::factory(),
            'book_copy_id' => fn (array $attributes): int => BookCopy::factory()->create([
                'book_id' => $attributes['book_id'],
            ])->id,
            'borrowed_at' => $borrowedAt,
            'due_at' => $borrowedAt->copy()->addDays(10),
            'extended_at' => null,
            'returned_at' => null,
            'return_reason' => null,
        ];
    }
}
