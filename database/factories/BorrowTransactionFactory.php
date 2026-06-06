<?php

namespace Database\Factories;

use App\Models\BookCopy;
use App\Models\BorrowTransaction;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BorrowTransaction>
 */
class BorrowTransactionFactory extends Factory
{
    public function definition(): array
    {
        $issuedAt = now()->subDays(fake()->numberBetween(1, 10));

        return [
            'transaction_code' => 'TRX-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'book_copy_id' => BookCopy::factory(),
            'member_id' => Member::factory(),
            'issued_by' => User::factory(),
            'returned_by' => null,
            'issued_at' => $issuedAt,
            'due_at' => $issuedAt->copy()->addDays(14),
            'returned_at' => null,
            'status' => BorrowTransaction::STATUS_BORROWED,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
