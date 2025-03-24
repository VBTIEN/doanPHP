<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Exam;
use App\Models\Score;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "Starting score seeding...\n";

        // Lấy tất cả học sinh
        $students = Student::all();
        if ($students->isEmpty()) {
            echo "No students found in the system.\n";
            return;
        }

        // Lấy tất cả bài kiểm tra
        $exams = Exam::all();
        if ($exams->isEmpty()) {
            echo "No exams found in the system.\n";
            return;
        }

        // Lấy tất cả điểm hiện có trong bảng scores
        $existingScores = Score::select('student_code', 'exam_code')
            ->get()
            ->mapWithKeys(function ($score) {
                return [$score->student_code . '_' . $score->exam_code => true];
            })
            ->toArray();

        $totalStudents = $students->count();
        $totalExams = $exams->count();
        echo "Found {$totalStudents} students and {$totalExams} exams.\n";

        $missingScoresCount = 0;

        // Duyệt qua từng học sinh và từng bài kiểm tra
        foreach ($students as $student) {
            foreach ($exams as $exam) {
                $key = $student->student_code . '_' . $exam->exam_code;

                // Kiểm tra xem cặp student_code và exam_code đã có điểm chưa
                if (!isset($existingScores[$key])) {
                    $missingScoresCount++;
                    $scoreValue = rand(10, 100) / 10; // Random điểm từ 1.0 đến 10.0

                    // Sử dụng Score::create để kích hoạt sự kiện Eloquent
                    try {
                        Score::create([
                            'student_code' => $student->student_code,
                            'exam_code' => $exam->exam_code,
                            'score_value' => $scoreValue,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        echo "Added score for student {$student->student_code} in exam {$exam->exam_code}: {$scoreValue}\n";
                    } catch (\Exception $e) {
                        echo "Error adding score for student {$student->student_code} in exam {$exam->exam_code}: {$e->getMessage()}\n";
                        continue;
                    }
                } else {
                    echo "Score already exists for student {$student->student_code} in exam {$exam->exam_code}, skipping...\n";
                }
            }
        }

        if ($missingScoresCount === 0) {
            echo "No missing scores to add.\n";
            return;
        }

        echo "Successfully added {$missingScoresCount} missing scores.\n";
        echo "Score seeding completed.\n";
    }
}