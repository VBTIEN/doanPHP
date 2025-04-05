<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Grade;
use App\Models\SchoolYear;
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
                'grade_level' => 'required|in:10,11,12', // Chỉ chấp nhận 10, 11, 12
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            // Lấy thông tin school year
            $schoolYear = SchoolYear::where('school_year_code', $validated['school_year_code'])->first();

            if (!$schoolYear) {
                return ResponseFormatter::fail(
                    'Năm học không tồn tại',
                    null,
                    404
                );
            }

            // Tạo grade_code theo định dạng G{grade_level}_{school_year_code}
            $grade_code = "G{$validated['grade_level']}_{$validated['school_year_code']}";
            
            // Tạo grade_name theo định dạng "Khối {grade_level} Năm {school_year_name}"
            $grade_name = "Khối {$validated['grade_level']} Năm {$schoolYear->school_year_name}";

            // Kiểm tra xem grade_code đã tồn tại chưa
            $existingGrade = Grade::where('grade_code', $grade_code)->first();
            if ($existingGrade) {
                return ResponseFormatter::fail(
                    "Khối với grade_code {$grade_code} đã tồn tại",
                    null,
                    400
                );
            }

            $grade = Grade::create([
                'grade_code' => $grade_code,
                'grade_name' => $grade_name,
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
                'grade_level' => 'required|in:10,11,12', // Chỉ chấp nhận 10, 11, 12
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            // Lấy thông tin school year
            $schoolYear = SchoolYear::where('school_year_code', $validated['school_year_code'])->first();

            if (!$schoolYear) {
                return ResponseFormatter::fail(
                    'Năm học không tồn tại',
                    null,
                    404
                );
            }

            // Tạo grade_code mới theo định dạng G{grade_level}_{school_year_code}
            $new_grade_code = "G{$validated['grade_level']}_{$validated['school_year_code']}";
            
            // Tạo grade_name mới theo định dạng "Khối {grade_level} Năm {school_year_name}"
            $new_grade_name = "Khối {$validated['grade_level']} Năm {$schoolYear->school_year_name}";

            // Nếu grade_code mới khác với hiện tại, kiểm tra xem nó đã tồn tại chưa
            if ($new_grade_code !== $grade->grade_code) {
                $existingGrade = Grade::where('grade_code', $new_grade_code)->first();
                if ($existingGrade) {
                    return ResponseFormatter::fail(
                        "Khối với grade_code {$new_grade_code} đã tồn tại",
                        null,
                        400
                    );
                }
            }

            // Cập nhật thông tin
            $grade->update([
                'grade_code' => $new_grade_code,
                'grade_name' => $new_grade_name,
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