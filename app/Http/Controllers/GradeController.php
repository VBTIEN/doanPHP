<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GradeController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $grades = Grade::all([
                'grade_code',
                'grade_name',
                'classroom_count',
                'school_year_code'
            ]);
            return ResponseFormatter::success(
                $grades,
                'Lấy danh sách khối thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in GradeController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'grade_name' => 'required|string|max:255|unique:grades,grade_name',
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            $grade = Grade::create([
                'grade_code' => 'G' . (Grade::count() + 1),
                'grade_name' => $validated['grade_name'],
                'classroom_count' => 0,
                'school_year_code' => $validated['school_year_code'],
            ]);

            return ResponseFormatter::success(
                $grade,
                'Tạo khối thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in GradeController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(string $grade_code): JsonResponse
    {
        try {
            $grade = Grade::where('grade_code', $grade_code)
                ->first([
                    'grade_code',
                    'grade_name',
                    'classroom_count',
                    'school_year_code'
                ]);

            if (!$grade) {
                return ResponseFormatter::fail(
                    'Khối không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $grade,
                'Lấy thông tin khối thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in GradeController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function update(Request $request, string $grade_code): JsonResponse
    {
        try {
            $grade = Grade::where('grade_code', $grade_code)->first();

            if (!$grade) {
                return ResponseFormatter::fail(
                    'Khối không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'grade_name' => 'required|string|max:255|unique:grades,grade_name,' . $grade->id,
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            $grade->update([
                'grade_name' => $validated['grade_name'],
                'school_year_code' => $validated['school_year_code'],
            ]);

            return ResponseFormatter::success(
                $grade,
                'Cập nhật khối thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in GradeController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(string $grade_code): JsonResponse
    {
        try {
            $grade = Grade::where('grade_code', $grade_code)->first();

            if (!$grade) {
                return ResponseFormatter::fail(
                    'Khối không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra nếu khối có Classroom liên quan
            if ($grade->classrooms()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa khối vì có lớp học liên quan',
                    null,
                    400
                );
            }

            $grade->delete();

            return ResponseFormatter::success(
                null,
                'Xóa khối thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in GradeController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa khối: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}