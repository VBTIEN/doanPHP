<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentTermAverage;
use App\Models\StudentYearlyAverage;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Support\Facades\Log;

class AcademicPerformanceController extends Controller
{
    /**
     * Lấy danh sách học sinh theo học lực trong một lớp (theo kỳ).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassroomTermPerformance(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'classroom_code' => 'required|string|exists:classrooms,classroom_code',
                'term_code' => 'required|string|exists:terms,term_code',
                'academic_performance' => 'required|string|in:Giỏi,Khá,Trung bình,Yếu',
            ]);

            $classroomCode = $request->input('classroom_code');
            $termCode = $request->input('term_code');
            $academicPerformance = $request->input('academic_performance');

            // Lấy classroom để kiểm tra
            $classroom = Classroom::where('classroom_code', $classroomCode)->first();
            if (!$classroom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Classroom not found.',
                ], 404);
            }

            // Lấy danh sách học sinh trong lớp
            $students = Student::where('classroom_code', $classroomCode)
                ->pluck('student_code')
                ->toArray();

            if (empty($students)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found in the specified classroom.',
                ], 404);
            }

            // Lấy danh sách học sinh theo học lực trong kỳ
            $studentsWithPerformance = StudentTermAverage::where('term_code', $termCode)
                ->whereIn('student_code', $students)
                ->where('academic_performance', $academicPerformance)
                ->get(['student_code', 'term_average', 'academic_performance'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'term_average' => $item->term_average,
                        'academic_performance' => $item->academic_performance,
                    ];
                });

            if ($studentsWithPerformance->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students found with academic performance '$academicPerformance' in the specified classroom and term.",
                ], 404);
            }

            // Tính tổng số học sinh thỏa mãn điều kiện học lực
            $totalStudents = $studentsWithPerformance->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'students' => $studentsWithPerformance,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomTermPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching classroom term performance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một lớp (theo năm).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassroomYearlyPerformance(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'classroom_code' => 'required|string|exists:classrooms,classroom_code',
                'academic_performance' => 'required|string|in:Giỏi,Khá,Trung bình,Yếu',
            ]);

            $classroomCode = $request->input('classroom_code');
            $academicPerformance = $request->input('academic_performance');

            // Lấy classroom và grade để lấy school_year_code
            $classroom = Classroom::where('classroom_code', $classroomCode)->first();
            if (!$classroom) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Classroom not found.',
                ], 404);
            }

            $grade = Grade::where('grade_code', $classroom->grade_code)->first();
            if (!$grade) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Grade not found for the specified classroom.',
                ], 404);
            }

            $schoolYearCode = $grade->school_year_code;

            // Lấy danh sách học sinh trong lớp
            $students = Student::where('classroom_code', $classroomCode)
                ->pluck('student_code')
                ->toArray();

            if (empty($students)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found in the specified classroom.',
                ], 404);
            }

            // Lấy danh sách học sinh theo học lực trong năm
            $studentsWithPerformance = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
                ->whereIn('student_code', $students)
                ->where('academic_performance', $academicPerformance)
                ->get(['student_code', 'yearly_average', 'academic_performance'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'yearly_average' => $item->yearly_average,
                        'academic_performance' => $item->academic_performance,
                    ];
                });

            if ($studentsWithPerformance->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students found with academic performance '$academicPerformance' in the specified classroom and school year.",
                ], 404);
            }

            // Tính tổng số học sinh thỏa mãn điều kiện học lực
            $totalStudents = $studentsWithPerformance->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'students' => $studentsWithPerformance,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomYearlyPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching classroom yearly performance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một khối (theo kỳ).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGradeTermPerformance(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'grade_code' => 'required|string|exists:grades,grade_code',
                'term_code' => 'required|string|exists:terms,term_code',
                'academic_performance' => 'required|string|in:Giỏi,Khá,Trung bình,Yếu',
            ]);

            $gradeCode = $request->input('grade_code');
            $termCode = $request->input('term_code');
            $academicPerformance = $request->input('academic_performance');

            // Lấy danh sách học sinh trong khối (dựa trên grade_code từ classroom)
            $students = Student::whereHas('classroom', function ($query) use ($gradeCode) {
                $query->where('grade_code', $gradeCode);
            })
                ->pluck('student_code')
                ->toArray();

            if (empty($students)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found in the specified grade.',
                ], 404);
            }

            // Lấy danh sách học sinh theo học lực trong kỳ
            $studentsWithPerformance = StudentTermAverage::where('term_code', $termCode)
                ->whereIn('student_code', $students)
                ->where('academic_performance', $academicPerformance)
                ->get(['student_code', 'term_average', 'academic_performance'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'term_average' => $item->term_average,
                        'academic_performance' => $item->academic_performance,
                    ];
                });

            if ($studentsWithPerformance->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students found with academic performance '$academicPerformance' in the specified grade and term.",
                ], 404);
            }

            // Tính tổng số học sinh thỏa mãn điều kiện học lực
            $totalStudents = $studentsWithPerformance->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'students' => $studentsWithPerformance,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeTermPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching grade term performance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy danh sách học sinh theo học lực trong một khối (theo năm).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGradeYearlyPerformance(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'grade_code' => 'required|string|exists:grades,grade_code',
                'academic_performance' => 'required|string|in:Giỏi,Khá,Trung bình,Yếu',
            ]);

            $gradeCode = $request->input('grade_code');
            $academicPerformance = $request->input('academic_performance');

            // Lấy grade để lấy school_year_code
            $grade = Grade::where('grade_code', $gradeCode)->first();
            if (!$grade) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Grade not found.',
                ], 404);
            }

            $schoolYearCode = $grade->school_year_code;

            // Lấy danh sách học sinh trong khối (dựa trên grade_code từ classroom)
            $students = Student::whereHas('classroom', function ($query) use ($gradeCode) {
                $query->where('grade_code', $gradeCode);
            })
                ->pluck('student_code')
                ->toArray();

            if (empty($students)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No students found in the specified grade.',
                ], 404);
            }

            // Lấy danh sách học sinh theo học lực trong năm
            $studentsWithPerformance = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
                ->whereIn('student_code', $students)
                ->where('academic_performance', $academicPerformance)
                ->get(['student_code', 'yearly_average', 'academic_performance'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'yearly_average' => $item->yearly_average,
                        'academic_performance' => $item->academic_performance,
                    ];
                });

            if ($studentsWithPerformance->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No students found with academic performance '$academicPerformance' in the specified grade and school year.",
                ], 404);
            }

            // Tính tổng số học sinh thỏa mãn điều kiện học lực
            $totalStudents = $studentsWithPerformance->count();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'students' => $studentsWithPerformance,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeYearlyPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching grade yearly performance.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}