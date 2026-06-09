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
        ['name' => 'Teknik Komputer', 'code' => 'TK'],
        ['name' => 'Teknik Sipil', 'code' => 'TS'],
        ['name' => 'Teknik Mesin', 'code' => 'TM'],
        ['name' => 'Teknik Industri', 'code' => 'TIND'],
        ['name' => 'Arsitektur', 'code' => 'ARS'],
        ['name' => 'Akuntansi', 'code' => 'AKT'],
        ['name' => 'Administrasi Bisnis', 'code' => 'ADB'],
        ['name' => 'Ilmu Hukum', 'code' => 'HK'],
        ['name' => 'Psikologi', 'code' => 'PSI'],
        ['name' => 'Kedokteran', 'code' => 'KED'],
        ['name' => 'Keperawatan', 'code' => 'KEP'],
        ['name' => 'Farmasi', 'code' => 'FAR'],
        ['name' => 'Kesehatan Masyarakat', 'code' => 'KM'],
        ['name' => 'Ilmu Komunikasi', 'code' => 'IKOM'],
        ['name' => 'Agribisnis', 'code' => 'AGB'],
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
