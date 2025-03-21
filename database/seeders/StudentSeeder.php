<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Student;
use App\Services\AuthService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Danh sách grade_code cụ thể cần xử lý
        $targetGrades = ['G10', 'G11', 'G12'];

        // Lấy danh sách các lớp thuộc các grade_code G10, G11, G12
        $classrooms = Classroom::where(function ($query) use ($targetGrades) {
            foreach ($targetGrades as $grade) {
                $query->orWhere('grade_code', 'like', "{$grade}%");
            }
        })->get();

        if ($classrooms->isEmpty()) {
            echo "No classrooms found for grades G10, G11, or G12.\n";
            return;
        }

        // Tạo instance của AuthService
        $authService = App::make(AuthService::class);

        // Lấy student_code lớn nhất hiện tại để bắt đầu
        $latestStudent = Student::orderBy('student_code', 'desc')->first();
        $currentNumber = $latestStudent ? (int) substr($latestStudent->student_code, 1) : 0;

        foreach ($classrooms as $classroom) {
            // Tính số học sinh còn thiếu để đạt tối đa 10
            $studentsToAdd = 10 - $classroom->student_count;

            if ($studentsToAdd <= 0) {
                echo "Classroom {$classroom->classroom_code} already has {$classroom->student_count} students, skipping...\n";
                continue;
            }

            // Lấy phần đầu của grade_code (G10, G11, G12)
            $gradePrefix = substr($classroom->grade_code, 0, 3);

            echo "Adding {$studentsToAdd} students to classroom {$classroom->classroom_code} (grade: {$classroom->grade_code})\n";

            // Tạo học sinh cho lớp này
            for ($i = 1; $i <= $studentsToAdd; $i++) {
                $currentNumber++; // Tăng số thứ tự cho student_code
                $studentCode = 'S' . $currentNumber;

                $studentData = [
                    'student_code' => $studentCode, // Truyền student_code vào dữ liệu
                    'name' => "Student {$gradePrefix} " . ($classroom->student_count + $i),
                    'email' => "student{$gradePrefix}{$currentNumber}@example.com", // Dùng $currentNumber để tránh trùng email
                    'password' => 'password123',
                    'role_code' => 'R2',
                    'grade_code' => $classroom->grade_code,
                ];

                try {
                    $student = $authService->createUser('R2', $studentData);
                    echo "Created student: {$studentData['email']} with code {$studentCode} for classroom {$classroom->classroom_code}\n";
                } catch (\Exception $e) {
                    echo "Error creating student: {$e->getMessage()}\n";
                    break;
                }
            }
        }

        echo "Student seeding completed.\n";
    }
}