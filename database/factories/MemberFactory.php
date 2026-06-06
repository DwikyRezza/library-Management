<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Member;
use App\Models\MemberCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_code' => 'MBR-'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'roll_number' => fake()->unique()->bothify('STU-####'),
            'branch_id' => Branch::factory(),
            'year' => fake()->numberBetween(1, 4),
            'member_category_id' => MemberCategory::factory(),
            'approved' => true,
            'rejected' => false,
            'approved_at' => now(),
            'books_borrowed_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'approved' => false,
            'rejected' => false,
            'approved_at' => null,
            'rejected_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'approved' => false,
            'rejected' => true,
            'approved_at' => null,
            'rejected_at' => now(),
        ]);
    }
}
