<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Term;
use App\Models\SchoolYear; // Thêm model SchoolYear
use Carbon\Carbon;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy danh sách subject_code và subject_name
        $subjects = Subject::select('subject_code', 'subject_name')->get()->keyBy('subject_code')->toArray();
        
        // Lấy danh sách term_code, start_date, end_date và school_year_code
        $terms = Term::select('term_code', 'start_date', 'end_date', 'school_year_code')
                     ->get()
                     ->keyBy('term_code')
                     ->toArray();

        // Lấy danh sách school_year_code và school_year_name
        $schoolYears = SchoolYear::select('school_year_code', 'school_year_name')
                                 ->get()
                                 ->keyBy('school_year_code')
                                 ->toArray();

        $examCounter = 1;
        $exams = [];

        foreach (array_keys($terms) as $termCode) {
            foreach (array_keys($subjects) as $subjectCode) {
                $startDate = Carbon::parse($terms[$termCode]['start_date']);
                $endDate = Carbon::parse($terms[$termCode]['end_date']);
                $midDate = $startDate->copy()->addDays((int) ($startDate->diffInDays($endDate) / 3));

                // Xác định học kỳ từ term_code (T1 là "Học kỳ một", T2 là "Học kỳ hai")
                $termNumber = explode('_', $termCode)[0]; // Lấy T1 hoặc T2
                $termName = $termNumber === 'T1' ? 'Học kỳ 1' : 'Học kỳ 2';

                // Lấy subject_name
                $subjectName = $subjects[$subjectCode]['subject_name'];

                // Lấy school_year_name từ school_year_code
                $schoolYearCode = $terms[$termCode]['school_year_code'];
                $schoolYearName = $schoolYears[$schoolYearCode]['school_year_name'];

                // Tạo exam_name cho kỳ thi giữa kỳ
                $exams[] = [
                    'exam_code' => "E" . $examCounter++,
                    'exam_name' => "Thi giữa $termName môn $subjectName năm học $schoolYearName",
                    'subject_code' => $subjectCode,
                    'term_code' => $termCode,
                    'date' => $midDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $finalDate = $endDate->copy()->subDays((int) ($startDate->diffInDays($endDate) / 6));

                // Tạo exam_name cho kỳ thi cuối kỳ
                $exams[] = [
                    'exam_code' => "E" . $examCounter++,
                    'exam_name' => "Thi cuối $termName môn $subjectName năm học $schoolYearName",
                    'subject_code' => $subjectCode,
                    'term_code' => $termCode,
                    'date' => $finalDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach ($exams as $examData) {
            Exam::updateOrCreate(
                ['exam_code' => $examData['exam_code']],
                $examData
            );
        }
    }
}