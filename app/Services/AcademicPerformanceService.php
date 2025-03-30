<?php

namespace App\Services;

use App\Models\StudentTermAverage;
use App\Models\StudentYearlyAverage;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Support\Facades\Log;

class AcademicPerformanceService
{
    /**
     * Lấy danh sách học sinh theo học lực trong một lớp (theo kỳ).
     *
     * @param string $classroomCode
     * @param string $termCode
     * @param string $academicPerformance
     * @return array
     * @throws \Exception
     */
    public function getClassroomTermPerformance($classroomCode, $termCode, $academicPerformance)
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

        // Lấy danh sách học sinh theo học lực trong kỳ
        $studentsWithPerformance = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $studentCodes)
            ->where('academic_performance', $academicPerformance)
            ->get(['student_code', 'term_average', 'academic_performance'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'term_average' => $item->term_average,
                    'academic_performance' => $item->academic_performance,
                ];
            });

        if ($studentsWithPerformance->isEmpty()) {
            throw new \Exception("No students found with academic performance '$academicPerformance' in the specified classroom and term.", 404);
        }

        $totalStudents = $studentsWithPerformance->count();

        return [
            'total_students' => $totalStudents,
            'students' => $studentsWithPerformance,
        ];
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một lớp (theo năm).
     *
     * @param string $classroomCode
     * @param string $academicPerformance
     * @return array
     * @throws \Exception
     */
    public function getClassroomYearlyPerformance($classroomCode, $academicPerformance)
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

        // Lấy danh sách học sinh theo học lực trong năm
        $studentsWithPerformance = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $studentCodes)
            ->where('academic_performance', $academicPerformance)
            ->get(['student_code', 'yearly_average', 'academic_performance'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'yearly_average' => $item->yearly_average,
                    'academic_performance' => $item->academic_performance,
                ];
            });

        if ($studentsWithPerformance->isEmpty()) {
            throw new \Exception("No students found with academic performance '$academicPerformance' in the specified classroom and school year.", 404);
        }

        $totalStudents = $studentsWithPerformance->count();

        return [
            'total_students' => $totalStudents,
            'students' => $studentsWithPerformance,
        ];
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một khối (theo kỳ).
     *
     * @param string $gradeCode
     * @param string $termCode
     * @param string $academicPerformance
     * @return array
     * @throws \Exception
     */
    public function getGradeTermPerformance($gradeCode, $termCode, $academicPerformance)
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

        // Lấy danh sách học sinh theo học lực trong kỳ
        $studentsWithPerformance = StudentTermAverage::where('term_code', $termCode)
            ->whereIn('student_code', $studentCodes)
            ->where('academic_performance', $academicPerformance)
            ->get(['student_code', 'term_average', 'academic_performance'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'term_average' => $item->term_average,
                    'academic_performance' => $item->academic_performance,
                ];
            });

        if ($studentsWithPerformance->isEmpty()) {
            throw new \Exception("No students found with academic performance '$academicPerformance' in the specified grade and term.", 404);
        }

        $totalStudents = $studentsWithPerformance->count();

        return [
            'total_students' => $totalStudents,
            'students' => $studentsWithPerformance,
        ];
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một khối (theo năm).
     *
     * @param string $gradeCode
     * @param string $academicPerformance
     * @return array
     * @throws \Exception
     */
    public function getGradeYearlyPerformance($gradeCode, $academicPerformance)
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

        // Lấy danh sách học sinh theo học lực trong năm
        $studentsWithPerformance = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
            ->whereIn('student_code', $studentCodes)
            ->where('academic_performance', $academicPerformance)
            ->get(['student_code', 'yearly_average', 'academic_performance'])
            ->map(function ($item) use ($studentNames) {
                return [
                    'student_code' => $item->student_code,
                    'name' => $studentNames[$item->student_code] ?? 'Unknown',
                    'yearly_average' => $item->yearly_average,
                    'academic_performance' => $item->academic_performance,
                ];
            });

        if ($studentsWithPerformance->isEmpty()) {
            throw new \Exception("No students found with academic performance '$academicPerformance' in the specified grade and school year.", 404);
        }

        $totalStudents = $studentsWithPerformance->count();

        return [
            'total_students' => $totalStudents,
            'students' => $studentsWithPerformance,
        ];
    }
}