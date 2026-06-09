<?php

namespace Tests\Feature\LibraFlow;

use App\Models\Branch;
use Database\Seeders\BranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_seeder_populates_study_programs_and_is_safe_to_run_repeatedly(): void
    {
        $this->seed(BranchSeeder::class);
        $this->seed(BranchSeeder::class);

        $this->assertSame(20, Branch::query()->count());
        $this->assertDatabaseHas('branches', [
            'name' => 'Information Technology',
            'code' => 'IT',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Informatics',
            'code' => 'INF',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Teknik Sipil',
            'code' => 'TS',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Akuntansi',
            'code' => 'AKT',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Psikologi',
            'code' => 'PSI',
        ]);
        $this->assertDatabaseHas('branches', [
            'name' => 'Kedokteran',
            'code' => 'KED',
        ]);
    }
}
