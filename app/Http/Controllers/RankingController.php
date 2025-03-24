<?php

namespace App\Http\Controllers;

use App\Services\AverageService;
use Illuminate\Http\Request;
use App\Models\StudentYearlyAverage;
use App\Models\StudentTermAverage; // Thêm model mới
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Support\Facades\Log;

class RankingController extends Controller
{
    protected $averageService;

    public function __construct(AverageService $averageService)
    {
        $this->averageService = $averageService;
    }

    /**
     * Lấy thứ hạng cả năm của học sinh trong một lớp cụ thể.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassroomYearlyRankings(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'classroom_code' => 'required|string|exists:classrooms,classroom_code',
            ]);

            $classroomCode = $request->input('classroom_code');

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

            // Tính tổng số học sinh trong lớp
            $totalStudents = count($students);

            // Lấy thứ hạng cả năm của học sinh trong lớp
            $rankings = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
                ->whereIn('student_code', $students)
                ->orderBy('yearly_average', 'desc')
                ->get(['student_code', 'yearly_average', 'classroom_rank'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'yearly_average' => $item->yearly_average,
                        'classroom_rank' => $item->classroom_rank,
                    ];
                });

            if ($rankings->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No yearly rankings found for the specified classroom.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'rankings' => $rankings,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomYearlyRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching classroom yearly rankings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thứ hạng cả năm của học sinh trong một khối cụ thể.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGradeYearlyRankings(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'grade_code' => 'required|string|exists:grades,grade_code',
            ]);

            $gradeCode = $request->input('grade_code');

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

            // Tính tổng số học sinh trong khối
            $totalStudents = count($students);

            // Lấy thứ hạng cả năm của học sinh trong khối
            $rankings = StudentYearlyAverage::where('school_year_code', $schoolYearCode)
                ->whereIn('student_code', $students)
                ->orderBy('yearly_average', 'desc')
                ->get(['student_code', 'yearly_average', 'grade_rank'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'yearly_average' => $item->yearly_average,
                        'grade_rank' => $item->grade_rank,
                    ];
                });

            if ($rankings->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No yearly rankings found for the specified grade.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'rankings' => $rankings,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeYearlyRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching grade yearly rankings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thứ hạng học kỳ của học sinh trong một lớp cụ thể.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClassroomTermRankings(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'classroom_code' => 'required|string|exists:classrooms,classroom_code',
                'term_code' => 'required|string|exists:terms,term_code',
            ]);

            $classroomCode = $request->input('classroom_code');
            $termCode = $request->input('term_code');

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

            // Tính tổng số học sinh trong lớp
            $totalStudents = count($students);

            // Lấy thứ hạng học kỳ của học sinh trong lớp
            $rankings = StudentTermAverage::where('term_code', $termCode)
                ->whereIn('student_code', $students)
                ->orderBy('term_average', 'desc')
                ->get(['student_code', 'term_average', 'classroom_rank'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'term_average' => $item->term_average,
                        'classroom_rank' => $item->classroom_rank,
                    ];
                });

            if ($rankings->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No term rankings found for the specified classroom and term.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'rankings' => $rankings,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomTermRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching classroom term rankings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lấy thứ hạng học kỳ của học sinh trong một khối cụ thể.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGradeTermRankings(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'grade_code' => 'required|string|exists:grades,grade_code',
                'term_code' => 'required|string|exists:terms,term_code',
            ]);

            $gradeCode = $request->input('grade_code');
            $termCode = $request->input('term_code');

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

            // Tính tổng số học sinh trong khối
            $totalStudents = count($students);

            // Lấy thứ hạng học kỳ của học sinh trong khối
            $rankings = StudentTermAverage::where('term_code', $termCode)
                ->whereIn('student_code', $students)
                ->orderBy('term_average', 'desc')
                ->get(['student_code', 'term_average', 'grade_rank'])
                ->map(function ($item) {
                    return [
                        'student_code' => $item->student_code,
                        'term_average' => $item->term_average,
                        'grade_rank' => $item->grade_rank,
                    ];
                });

            if ($rankings->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No term rankings found for the specified grade and term.',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_students' => $totalStudents,
                    'rankings' => $rankings,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeTermRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while fetching grade term rankings.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}