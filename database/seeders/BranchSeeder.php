<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public const BRANCHES = [
        ['name' => 'Information Technology', 'code' => 'IT'],
        ['name' => 'Informatics', 'code' => 'INF'],
        ['name' => 'Information System', 'code' => 'IS'],
        ['name' => 'Electrical Engineering', 'code' => 'EE'],
        ['name' => 'Management', 'code' => 'MGT'],
    ];

    /**
     * Seed the study programs required by member profile forms.
     */
    public function run(): void
    {
        foreach (self::BRANCHES as $branch) {
            Branch::query()->updateOrCreate(
                ['name' => $branch['name']],
                ['code' => $branch['code']],
            );
        }
    }
}
