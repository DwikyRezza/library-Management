<?php

namespace Tests\Feature\LibraFlow;

use App\Models\BookCategory;
use Database\Seeders\BookCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCategorySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_category_seeder_populates_categories_without_removing_existing_data(): void
    {
        BookCategory::factory()->create([
            'name' => 'Kategori Kampus',
            'slug' => 'kategori-kampus',
        ]);

        $this->seed(BookCategorySeeder::class);
        $this->seed(BookCategorySeeder::class);

        $this->assertSame(16, BookCategory::query()->count());
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Technology',
            'slug' => 'technology',
        ]);
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Education',
            'slug' => 'education',
        ]);
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Medicine',
            'slug' => 'medicine',
        ]);
        $this->assertDatabaseHas('book_categories', [
            'name' => 'Kategori Kampus',
            'slug' => 'kategori-kampus',
        ]);
    }
}
