<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubjectController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $subjects = Subject::all(['subject_code', 'subject_name']);
            return ResponseFormatter::success(
                $subjects,
                'Lấy danh sách môn học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SubjectController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'subject_code' => 'required|string|max:10|unique:subjects,subject_code',
                'subject_name' => 'required|string|max:255|unique:subjects,subject_name',
            ]);

            $subject = Subject::create([
                'subject_code' => $validated['subject_code'],
                'subject_name' => $validated['subject_name'],
            ]);

            return ResponseFormatter::success(
                $subject,
                'Tạo môn học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SubjectController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(string $subject_code): JsonResponse
    {
        try {
            $subject = Subject::where('subject_code', $subject_code)
                ->first(['subject_code', 'subject_name']);

            if (!$subject) {
                return ResponseFormatter::fail(
                    'Môn học không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $subject,
                'Lấy thông tin môn học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SubjectController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function update(Request $request, string $subject_code): JsonResponse
    {
        try {
            $subject = Subject::where('subject_code', $subject_code)->first();

            if (!$subject) {
                return ResponseFormatter::fail(
                    'Môn học không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'new_subject_code' => 'required|string|max:10|unique:subjects,subject_code,' . $subject->id,
                'subject_name' => 'required|string|max:255|unique:subjects,subject_name,' . $subject->id,
            ]);

            $subject->update([
                'subject_code' => $validated['new_subject_code'],
                'subject_name' => $validated['subject_name'],
            ]);

            return ResponseFormatter::success(
                $subject,
                'Cập nhật môn học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SubjectController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(string $subject_code): JsonResponse
    {
        try {
            $subject = Subject::where('subject_code', $subject_code)->first();

            if (!$subject) {
                return ResponseFormatter::fail(
                    'Môn học không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra nếu môn học có Teacher hoặc Score liên quan
            if ($subject->teachers()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa môn học vì có giáo viên hoặc điểm số liên quan',
                    null,
                    400
                );
            }

            $subject->delete();

            return ResponseFormatter::success(
                null,
                'Xóa môn học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SubjectController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}