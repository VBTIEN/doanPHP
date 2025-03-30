<?php

namespace App\Http\Controllers;

use App\Services\RankingService;
use App\Services\AverageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RankingController extends Controller
{
    protected $averageService;
    protected $rankingService;

    public function __construct(AverageService $averageService, RankingService $rankingService)
    {
        $this->averageService = $averageService;
        $this->rankingService = $rankingService;
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

            $result = $this->rankingService->getClassroomYearlyRankings($classroomCode);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomYearlyRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->rankingService->getGradeYearlyRankings($gradeCode);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeYearlyRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->rankingService->getClassroomTermRankings($classroomCode, $termCode);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getClassroomTermRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
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

            $result = $this->rankingService->getGradeTermRankings($gradeCode, $termCode);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error in getGradeTermRankings: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}