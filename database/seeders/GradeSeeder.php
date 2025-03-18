<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SchoolYear;
use App\Models\Grade;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolYears = SchoolYear::all();
        $grades = [];

        foreach ($schoolYears as $schoolYear) {
            $grades[] = [
                'grade_code' => "G10_{$schoolYear->school_year_code}", // Định dạng mới: G10_SY_2024-2025
                'grade_name' => "Khối 10 Năm {$schoolYear->school_year_name}",
                'school_year_code' => $schoolYear->school_year_code, // Định dạng mới: SY_2024-2025
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $grades[] = [
                'grade_code' => "G11_{$schoolYear->school_year_code}", // Định dạng mới: G11_SY_2024-2025
                'grade_name' => "Khối 11 Năm {$schoolYear->school_year_name}",
                'school_year_code' => $schoolYear->school_year_code, // Định dạng mới: SY_2024-2025
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $grades[] = [
                'grade_code' => "G12_{$schoolYear->school_year_code}", // Định dạng mới: G12_SY_2024-2025
                'grade_name' => "Khối 12 Năm {$schoolYear->school_year_name}",
                'school_year_code' => $schoolYear->school_year_code, // Định dạng mới: SY_2024-2025
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($grades as $gradeData) {
            Grade::updateOrCreate(
                ['grade_code' => $gradeData['grade_code']],
                $gradeData
            );
        }
    }
}
