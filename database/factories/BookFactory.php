<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BookCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'publication_year' => fake()->numberBetween(1998, 2026),
            'isbn' => fake()->unique()->isbn13(),
            'description' => fake()->paragraph(),
            'category_id' => BookCategory::factory(),
            'total_copies' => 0,
            'available_copies' => 0,
        ];
    }
}
