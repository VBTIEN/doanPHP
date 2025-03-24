<?php

namespace App\Services;

use App\Models\Score;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * Lấy danh sách điểm của học sinh với bộ lọc tùy chọn.
     *
     * @param string $studentCode Mã học sinh
     * @param string|null $subjectCode Mã môn học (tùy chọn)
     * @param string|null $termCode Mã kỳ học (tùy chọn)
     * @return array Danh sách điểm
     */
    public function getStudentScores(string $studentCode, ?string $subjectCode = null, ?string $termCode = null): array
    {
        // Truy vấn bảng scores và join với exams để lấy term_code và subject_code
        $query = Score::select(
            'scores.exam_code',
            'exams.term_code',
            'exams.subject_code',
            'scores.score_value'
        )
            ->join('exams', 'scores.exam_code', '=', 'exams.exam_code')
            ->where('scores.student_code', $studentCode);

        // Áp dụng bộ lọc subject_code nếu có
        if ($subjectCode) {
            $query->where('exams.subject_code', $subjectCode);
        }

        // Áp dụng bộ lọc term_code nếu có
        if ($termCode) {
            $query->where('exams.term_code', $termCode);
        }

        // Lấy kết quả
        $scores = $query->get()->map(function ($score) {
            return [
                'exam_code' => $score->exam_code,
                'term_code' => $score->term_code,
                'subject_code' => $score->subject_code,
                'score_value' => $score->score_value,
            ];
        })->toArray();

        return $scores;
    }
}