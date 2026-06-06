<?php

namespace Database\Factories;

use App\Models\MemberCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberCategory>
 */
class MemberCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'max_books' => fake()->numberBetween(3, 8),
            'loan_days' => 14,
            'description' => fake()->sentence(),
        ];
    }
}
