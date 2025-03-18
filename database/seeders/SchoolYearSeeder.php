<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolYear;

class SchoolYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolYears = [];

        for ($year = 2024; $year <= 2025; $year++) {
            $nextYear = $year + 1;
            $schoolYears[] = [
                'school_year_code' => "SY_{$year}-{$nextYear}",
                'school_year_name' => "{$year}-{$nextYear}",
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($schoolYears as $schoolYearData) {
            SchoolYear::updateOrCreate(
                ['school_year_code' => $schoolYearData['school_year_code']],
                $schoolYearData
            );
        }
    }
}
