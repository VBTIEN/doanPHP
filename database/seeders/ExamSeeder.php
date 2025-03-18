<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\Term;
use Carbon\Carbon;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = Subject::pluck('subject_code')->toArray();
        $terms = Term::pluck('term_code')->toArray();
        $termDetails = Term::select('term_code', 'start_date', 'end_date')
                           ->get()
                           ->keyBy('term_code');

        $examCounter = 1;
        $exams = [];

        foreach ($terms as $termCode) {
            foreach ($subjects as $subjectCode) {
                $startDate = Carbon::parse($termDetails[$termCode]->start_date);
                $endDate = Carbon::parse($termDetails[$termCode]->end_date);
                $midDate = $startDate->copy()->addDays((int) ($startDate->diffInDays($endDate) / 3));
                $exams[] = [
                    'exam_code' => "E" . $examCounter++, // Tách ++ ra ngoài
                    'exam_name' => 'Thi giữa kỳ',
                    'subject_code' => $subjectCode,
                    'term_code' => $termCode, // Ví dụ: T1_2024-2025
                    'date' => $midDate->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $finalDate = $endDate->copy()->subDays((int) ($startDate->diffInDays($endDate) / 6));
                $exams[] = [
                    'exam_code' => "E" . $examCounter++, // Tách ++ ra ngoài
                    'exam_name' => 'Thi cuối kỳ',
                    'subject_code' => $subjectCode,
                    'term_code' => $termCode, // Ví dụ: T1_2024-2025
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
