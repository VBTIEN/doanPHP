<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Term;
use App\Models\SchoolYear;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schoolYears = SchoolYear::all();
        $terms = [];

        foreach ($schoolYears as $schoolYear) {
            // Lấy năm bắt đầu và kết thúc từ school_year_name (ví dụ: "2024-2025")
            [$startYear, $endYear] = explode('-', $schoolYear->school_year_name);

            // Học kỳ 1
            $terms[] = [
                'term_code' => "T1_{$startYear}-{$endYear}", // T1_2024-2025
                'term_name' => "Học kỳ 1 Năm {$startYear}-{$endYear}", // Học kỳ 1 Năm 2024-2025
                'start_date' => "{$startYear}-09-01", // Bắt đầu từ 01/09
                'end_date' => "{$startYear}-12-31",   // Kết thúc 31/12
                'school_year_code' => $schoolYear->school_year_code, // Định dạng: SY_2024-2025
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Học kỳ 2
            $terms[] = [
                'term_code' => "T2_{$startYear}-{$endYear}", // T2_2024-2025
                'term_name' => "Học kỳ 2 Năm {$startYear}-{$endYear}", // Học kỳ 2 Năm 2024-2025
                'start_date' => "{$endYear}-01-01", // Bắt đầu từ 01/01 năm sau
                'end_date' => "{$endYear}-05-31",   // Kết thúc 31/05 năm sau
                'school_year_code' => $schoolYear->school_year_code, // Định dạng: SY_2024-2025
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach ($terms as $termData) {
            Term::updateOrCreate(
                ['term_code' => $termData['term_code']],
                $termData
            );
        }
    }
}
