<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SchoolYearController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $schoolYears = SchoolYear::all(['school_year_code', 'school_year_name']);
            return ResponseFormatter::success(
                $schoolYears,
                'Lấy danh sách năm học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SchoolYearController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_year' => 'required|integer|min:2000|max:9999', // Năm bắt đầu
            ]);

            $startYear = $validated['start_year'];
            $nextYear = $startYear + 1;

            // Tạo school_year_code theo định dạng SY_{start_year}-{next_year}
            $school_year_code = "SY_{$startYear}-{$nextYear}";

            // Tạo school_year_name theo định dạng {start_year}-{next_year}
            $school_year_name = "{$startYear}-{$nextYear}";

            // Kiểm tra xem school_year_code đã tồn tại chưa
            $existingSchoolYear = SchoolYear::where('school_year_code', $school_year_code)->first();
            if ($existingSchoolYear) {
                return ResponseFormatter::fail(
                    "Năm học với school_year_code {$school_year_code} đã tồn tại",
                    null,
                    400
                );
            }

            // Kiểm tra uniqueness của school_year_name
            $nameExists = SchoolYear::where('school_year_name', $school_year_name)->first();
            if ($nameExists) {
                return ResponseFormatter::fail(
                    "Năm học với school_year_name {$school_year_name} đã tồn tại",
                    null,
                    400
                );
            }

            $schoolYear = SchoolYear::create([
                'school_year_code' => $school_year_code,
                'school_year_name' => $school_year_name,
            ]);

            return ResponseFormatter::success(
                $schoolYear,
                'Tạo năm học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SchoolYearController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(string $school_year_code): JsonResponse
    {
        try {
            $schoolYear = SchoolYear::where('school_year_code', $school_year_code)
                ->first(['school_year_code', 'school_year_name']);

            if (!$schoolYear) {
                return ResponseFormatter::fail(
                    'Năm học không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $schoolYear,
                'Lấy thông tin năm học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SchoolYearController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function update(Request $request, string $school_year_code): JsonResponse
    {
        try {
            $schoolYear = SchoolYear::where('school_year_code', $school_year_code)->first();

            if (!$schoolYear) {
                return ResponseFormatter::fail(
                    'Năm học không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'start_year' => 'required|integer|min:2000|max:9999', // Năm bắt đầu
            ]);

            $startYear = $validated['start_year'];
            $nextYear = $startYear + 1;

            // Tạo school_year_code mới
            $new_school_year_code = "SY_{$startYear}-{$nextYear}";

            // Tạo school_year_name mới
            $new_school_year_name = "{$startYear}-{$nextYear}";

            // Kiểm tra uniqueness của school_year_code mới (nếu thay đổi)
            if ($new_school_year_code !== $schoolYear->school_year_code) {
                $codeExists = SchoolYear::where('school_year_code', $new_school_year_code)->first();
                if ($codeExists) {
                    return ResponseFormatter::fail(
                        "Năm học với school_year_code {$new_school_year_code} đã tồn tại",
                        null,
                        400
                    );
                }
            }

            // Kiểm tra uniqueness của school_year_name mới (nếu thay đổi)
            if ($new_school_year_name !== $schoolYear->school_year_name) {
                $nameExists = SchoolYear::where('school_year_name', $new_school_year_name)->first();
                if ($nameExists) {
                    return ResponseFormatter::fail(
                        "Năm học với school_year_name {$new_school_year_name} đã tồn tại",
                        null,
                        400
                    );
                }
            }

            $schoolYear->update([
                'school_year_code' => $new_school_year_code,
                'school_year_name' => $new_school_year_name,
            ]);

            return ResponseFormatter::success(
                $schoolYear,
                'Cập nhật năm học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SchoolYearController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(string $school_year_code): JsonResponse
    {
        try {
            $schoolYear = SchoolYear::where('school_year_code', $school_year_code)->first();

            if (!$schoolYear) {
                return ResponseFormatter::fail(
                    'Năm học không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra nếu năm học có Term hoặc Grade liên quan
            if ($schoolYear->terms()->exists() || $schoolYear->grades()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa năm học vì có học kỳ hoặc khối liên quan',
                    null,
                    400
                );
            }

            $schoolYear->delete();

            return ResponseFormatter::success(
                null,
                'Xóa năm học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in SchoolYearController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa năm học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}