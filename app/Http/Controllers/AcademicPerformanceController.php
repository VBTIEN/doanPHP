<?php

namespace App\Http\Controllers;

use App\Services\AcademicPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AcademicPerformanceController extends Controller
{
    protected $academicPerformanceService;

    public function __construct(AcademicPerformanceService $academicPerformanceService)
    {
        $this->academicPerformanceService = $academicPerformanceService;
    }

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

            $result = $this->academicPerformanceService->getClassroomTermPerformance($classroomCode, $termCode, $academicPerformance);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomTermPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->academicPerformanceService->getClassroomYearlyPerformance($classroomCode, $academicPerformance);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomYearlyPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->academicPerformanceService->getGradeTermPerformance($gradeCode, $termCode, $academicPerformance);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeTermPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->academicPerformanceService->getGradeYearlyPerformance($gradeCode, $academicPerformance);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeYearlyPerformance: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}