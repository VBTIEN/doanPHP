<?php

namespace App\Services;

use App\Models\StudentYearlyAverage;
use App\Models\StudentTermAverage;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Support\Facades\Log;

class RankingService
{
    /**
     * Lấy thứ hạng cả năm của học sinh trong một lớp cụ thể.
     *
     * @param string $classroomCode
     * @return array
     * @throws \Exception
     */
    public function getClassroomYearlyRankings($classroomCode)
    {
        // Lấy classroom và grade để lấy school_year_code
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Classroom not found.', 404);
        }

        $grade = Grade::where('grade_code', $classroom->grade_code)->first();
        if (!$grade) {
            throw new \Exception('Grade not found for the specified classroom.', 404);
        }

        $schoolYearCode = $grade->school_year_code;

        // Lấy danh sách học sinh trong lớp
        $students = Student::where('classroom_code', $classroomCode)
            ->get(['student_code', 'name']);

        if ($students->isEmpty()) {
            throw new \Exception('No students found in the specified classroom.', 404);
        }

        $studentCodes = $students->pluck('student_code')->toArray();
        $studentNames = $students->pluck('name', 'student_code')->toArray();
        $totalStudents = count($studentCodes);

        // Lấy thứ hạng cả năm của học sinh trong lớp
        $rankings = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $studentCodes)
            ->orderBy('yearly_average', 'desc')
            ->get(['student_code', 'yearly_average', 'classroom_rank'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'yearly_average' => $item->yearly_average,
                    'classroom_rank' => $item->classroom_rank,
                ];
            });

        if ($rankings->isEmpty()) {
            throw new \Exception('No yearly rankings found for the specified classroom.', 404);
        }

        return [
            'total_students' => $totalStudents,
            'rankings' => $rankings,
        ];
    }

    /**
     * Lấy thứ hạng cả năm của học sinh trong một khối cụ thể.
     *
     * @param string $gradeCode
     * @return array
     * @throws \Exception
     */
    public function getGradeYearlyRankings($gradeCode)
    {
        // Lấy grade để lấy school_year_code
        $grade = Grade::where('grade_code', $gradeCode)->first();
        if (!$grade) {
            throw new \Exception('Grade not found.', 404);
        }

        $schoolYearCode = $grade->school_year_code;

        // Lấy danh sách học sinh trong khối
        $students = Student::whereHas('classroom', function ($query) use ($gradeCode) {
            $query->where('grade_code', $gradeCode);
        })->get(['student_code', 'name']);

        if ($students->isEmpty()) {
            throw new \Exception('No students found in the specified grade.', 404);
        }

        $studentCodes = $students->pluck('student_code')->toArray();
        $studentNames = $students->pluck('name', 'student_code')->toArray();
        $totalStudents = count($studentCodes);

        // Lấy thứ hạng cả năm của học sinh trong khối
        $rankings = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $studentCodes)
            ->orderBy('yearly_average', 'desc')
            ->get(['student_code', 'yearly_average', 'grade_rank'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'yearly_average' => $item->yearly_average,
                    'grade_rank' => $item->grade_rank,
                ];
            });

        if ($rankings->isEmpty()) {
            throw new \Exception('No yearly rankings found for the specified grade.', 404);
        }

        return [
            'total_students' => $totalStudents,
            'rankings' => $rankings,
        ];
    }

    /**
     * Lấy thứ hạng học kỳ của học sinh trong một lớp cụ thể.
     *
     * @param string $classroomCode
     * @param string $termCode
     * @return array
     * @throws \Exception
     */
    public function getClassroomTermRankings($classroomCode, $termCode)
    {
        // Lấy classroom để kiểm tra
        $classroom = Classroom::where('classroom_code', $classroomCode)->first();
        if (!$classroom) {
            throw new \Exception('Classroom not found.', 404);
        }

        // Lấy danh sách học sinh trong lớp
        $students = Student::where('classroom_code', $classroomCode)
            ->get(['student_code', 'name']);

        if ($students->isEmpty()) {
            throw new \Exception('No students found in the specified classroom.', 404);
        }

        $studentCodes = $students->pluck('student_code')->toArray();
        $studentNames = $students->pluck('name', 'student_code')->toArray();
        $totalStudents = count($studentCodes);

        // Lấy thứ hạng học kỳ của học sinh trong lớp
        $rankings = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $studentCodes)
            ->orderBy('term_average', 'desc')
            ->get(['student_code', 'term_average', 'classroom_rank'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'term_average' => $item->term_average,
                    'classroom_rank' => $item->classroom_rank,
                ];
            });

        if ($rankings->isEmpty()) {
            throw new \Exception('No term rankings found for the specified classroom and term.', 404);
        }

        return [
            'total_students' => $totalStudents,
            'rankings' => $rankings,
        ];
    }

    /**
     * Lấy thứ hạng học kỳ của học sinh trong một khối cụ thể.
     *
     * @param string $gradeCode
     * @param string $termCode
     * @return array
     * @throws \Exception
     */
    public function getGradeTermRankings($gradeCode, $termCode)
    {
        // Lấy danh sách học sinh trong khối
        $students = Student::whereHas('classroom', function ($query) use ($gradeCode) {
            $query->where('grade_code', $gradeCode);
        })->get(['student_code', 'name']);

        if ($students->isEmpty()) {
            throw new \Exception('No students found in the specified grade.', 404);
        }

        $studentCodes = $students->pluck('student_code')->toArray();
        $studentNames = $students->pluck('name', 'student_code')->toArray();
        $totalStudents = count($studentCodes);

        // Lấy thứ hạng học kỳ của học sinh trong khối
        $rankings = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $studentCodes)
            ->orderBy('term_average', 'desc')
            ->get(['student_code', 'term_average', 'grade_rank'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'term_average' => $item->term_average,
                    'grade_rank' => $item->grade_rank,
                ];
            });

        if ($rankings->isEmpty()) {
            throw new \Exception('No term rankings found for the specified grade and term.', 404);
        }

        return [
            'total_students' => $totalStudents,
            'rankings' => $rankings,
        ];
    }
}