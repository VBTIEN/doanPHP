<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExamController extends Controller
{
    /**
     * Lấy danh sách tất cả các kỳ thi.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $exams = Exam::with(['subject', 'term'])->get([
                'exam_code',
                'exam_name',
                'subject_code',
                'term_code',
                'date'
            ]);
            return ResponseFormatter::success(
                $exams,
                'Lấy danh sách kỳ thi thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ExamController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Tạo một kỳ thi mới.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'exam_name' => 'required|string|max:255|unique:exams,exam_name',
                'subject_code' => 'required|exists:subjects,subject_code',
                'term_code' => 'required|exists:terms,term_code',
                'date' => 'required|date',
            ]);

            $exam = Exam::create([
                'exam_code' => 'E' . (Exam::count() + 1),
                'exam_name' => $validated['exam_name'],
                'subject_code' => $validated['subject_code'],
                'term_code' => $validated['term_code'],
                'date' => $validated['date'],
            ]);

            $exam->load(['subject', 'term']); // Load thông tin subject và term sau khi tạo

            return ResponseFormatter::success(
                $exam,
                'Tạo kỳ thi thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ExamController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Lấy thông tin chi tiết của một kỳ thi.
     *
     * @param string $exam_code
     * @return JsonResponse
     */
    public function show(string $exam_code): JsonResponse
    {
        try {
            $exam = Exam::with(['subject', 'term'])
                ->where('exam_code', $exam_code)
                ->first([
                    'exam_code',
                    'exam_name',
                    'subject_code',
                    'term_code',
                    'date'
                ]);

            if (!$exam) {
                return ResponseFormatter::fail(
                    'Kỳ thi không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $exam,
                'Lấy thông tin kỳ thi thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ExamController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Cập nhật thông tin một kỳ thi.
     *
     * @param Request $request
     * @param string $exam_code
     * @return JsonResponse
     */
    public function update(Request $request, string $exam_code): JsonResponse
    {
        try {
            $exam = Exam::where('exam_code', $exam_code)->first();

            if (!$exam) {
                return ResponseFormatter::fail(
                    'Kỳ thi không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'exam_name' => 'required|string|max:255|unique:exams,exam_name,' . $exam->id,
                'subject_code' => 'required|exists:subjects,subject_code',
                'term_code' => 'required|exists:terms,term_code',
                'date' => 'required|date',
            ]);

            $exam->update([
                'exam_name' => $validated['exam_name'],
                'subject_code' => $validated['subject_code'],
                'term_code' => $validated['term_code'],
                'date' => $validated['date'],
            ]);

            $exam->load(['subject', 'term']); // Load thông tin subject và term sau khi cập nhật

            return ResponseFormatter::success(
                $exam,
                'Cập nhật kỳ thi thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ExamController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Xóa một kỳ thi.
     *
     * @param string $exam_code
     * @return JsonResponse
     */
    public function destroy(string $exam_code): JsonResponse
    {
        try {
            $exam = Exam::where('exam_code', $exam_code)->first();

            if (!$exam) {
                return ResponseFormatter::fail(
                    'Kỳ thi không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra xem kỳ thi có điểm số liên quan không
            if ($exam->scores()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa kỳ thi vì đã có điểm số liên quan',
                    null,
                    400
                );
            }

            $exam->delete();

            return ResponseFormatter::success(
                null,
                'Xóa kỳ thi thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ExamController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa kỳ thi: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}