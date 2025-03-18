<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classroom;
use App\Models\Grade;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = Grade::all();
        $classrooms = [];

        foreach ($grades as $grade) {
            if (strpos($grade->grade_code, 'G10') === 0) {
                $classrooms[] = [
                    'classroom_code' => "C1_{$grade->grade_code}", // Ví dụ: C1_G10_SY_2024-2025
                    'classroom_name' => '10A',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $classrooms[] = [
                    'classroom_code' => "C2_{$grade->grade_code}", // Ví dụ: C2_G10_SY_2024-2025
                    'classroom_name' => '10B',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } elseif (strpos($grade->grade_code, 'G11') === 0) {
                $classrooms[] = [
                    'classroom_code' => "C3_{$grade->grade_code}", // Ví dụ: C3_G11_SY_2024-2025
                    'classroom_name' => '11A',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $classrooms[] = [
                    'classroom_code' => "C4_{$grade->grade_code}", // Ví dụ: C4_G11_SY_2024-2025
                    'classroom_name' => '11B',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } elseif (strpos($grade->grade_code, 'G12') === 0) {
                $classrooms[] = [
                    'classroom_code' => "C5_{$grade->grade_code}", // Ví dụ: C5_G12_SY_2024-2025
                    'classroom_name' => '12A',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $classrooms[] = [
                    'classroom_code' => "C6_{$grade->grade_code}", // Ví dụ: C6_G12_SY_2024-2025
                    'classroom_name' => '12B',
                    'grade_code' => $grade->grade_code,
                    'student_count' => 0,
                    'homeroom_teacher_code' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($classrooms as $classroomData) {
            Classroom::updateOrCreate(
                ['classroom_code' => $classroomData['classroom_code']],
                $classroomData
            );
        }

        // Cập nhật classroom_count cho từng Grade
        foreach ($grades as $grade) {
            $grade->classroom_count = Classroom::where('grade_code', $grade->grade_code)->count();
            $grade->save();
        }
    }
}
