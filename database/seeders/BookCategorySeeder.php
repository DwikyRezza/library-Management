<?php

namespace Database\Seeders;

use App\Models\BookCategory;
use Illuminate\Database\Seeder;

class BookCategorySeeder extends Seeder
{
    public const CATEGORIES = [
        [
            'name' => 'Technology',
            'slug' => 'technology',
            'color' => 'blue',
            'description' => 'Programming, information systems, computers, and digital technology.',
        ],
        [
            'name' => 'Science',
            'slug' => 'science',
            'color' => 'emerald',
            'description' => 'Natural sciences, mathematics, research, and scientific discovery.',
        ],
        [
            'name' => 'Fiction',
            'slug' => 'fiction',
            'color' => 'indigo',
            'description' => 'Novels, short stories, and other works of creative literature.',
        ],
        [
            'name' => 'Business',
            'slug' => 'business',
            'color' => 'amber',
            'description' => 'Management, entrepreneurship, marketing, and business strategy.',
        ],
        [
            'name' => 'History',
            'slug' => 'history',
            'color' => 'rose',
            'description' => 'Historical events, cultures, societies, and civilizations.',
        ],
        [
            'name' => 'Education',
            'slug' => 'education',
            'color' => 'blue',
            'description' => 'Teaching methods, learning development, and educational studies.',
        ],
        [
            'name' => 'Religion',
            'slug' => 'religion',
            'color' => 'emerald',
            'description' => 'Religion, theology, spirituality, and comparative belief studies.',
        ],
        [
            'name' => 'Philosophy',
            'slug' => 'philosophy',
            'color' => 'indigo',
            'description' => 'Ethics, logic, ideas, and philosophical thought.',
        ],
        [
            'name' => 'Psychology',
            'slug' => 'psychology',
            'color' => 'rose',
            'description' => 'Human behavior, mental processes, and personal development.',
        ],
        [
            'name' => 'Law',
            'slug' => 'law',
            'color' => 'amber',
            'description' => 'Legal systems, regulations, public policy, and jurisprudence.',
        ],
        [
            'name' => 'Medicine',
            'slug' => 'medicine',
            'color' => 'rose',
            'description' => 'Medicine, healthcare, anatomy, and clinical references.',
        ],
        [
            'name' => 'Engineering',
            'slug' => 'engineering',
            'color' => 'blue',
            'description' => 'Civil, mechanical, electrical, industrial, and applied engineering.',
        ],
        [
            'name' => 'Economics',
            'slug' => 'economics',
            'color' => 'emerald',
            'description' => 'Economic theory, finance, development, and public economics.',
        ],
        [
            'name' => 'Biography',
            'slug' => 'biography',
            'color' => 'indigo',
            'description' => 'Biographies, memoirs, and stories of influential people.',
        ],
        [
            'name' => 'Arts',
            'slug' => 'arts',
            'color' => 'amber',
            'description' => 'Visual arts, design, music, theater, and cultural expression.',
        ],
        [
            'name' => 'Self Development',
            'slug' => 'self-development',
            'color' => 'blue',
            'description' => 'Personal growth, productivity, habits, communication, and motivation.',
        ],
    ];

    /**
     * Seed the book categories required by catalog and book forms.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            BookCategory::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => $category['slug'],
                    'color' => $category['color'],
                    'description' => $category['description'],
                ],
            );
        }
    }
}
