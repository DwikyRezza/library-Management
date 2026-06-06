<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCopy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookCopy>
 */
class BookCopyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'copy_code' => 'LIB-'.fake()->unique()->numerify('####-###'),
            'shelf_location' => fake()->bothify('A-##'),
            'status' => BookCopy::STATUS_AVAILABLE,
            'condition_note' => null,
        ];
    }
}
