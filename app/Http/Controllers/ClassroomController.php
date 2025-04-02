<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Classroom;
use App\Models\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClassroomController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $classrooms = Classroom::with('grade')->get([
                'classroom_code',
                'classroom_name',
                'grade_code',
                'student_count',
                'homeroom_teacher_code'
            ]);
            return ResponseFormatter::success(
                $classrooms,
                'Lấy danh sách lớp học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ClassroomController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'classroom_name' => 'required|string|max:255|unique:classrooms,classroom_name',
                'grade_code' => 'required|exists:grades,grade_code',
            ]);

            $grade = Grade::where('grade_code', $validated['grade_code'])->first();
            if (!$grade) {
                return ResponseFormatter::fail(
                    'Khối không tồn tại',
                    null,
                    404
                );
            }

            // Tạo classroom_code theo định dạng C{number}_{grade_code}
            $classroomCount = Classroom::where('grade_code', $validated['grade_code'])->count() + 1;
            $classroomCode = "C{$classroomCount}_{$validated['grade_code']}";

            // Kiểm tra classroom_name có phù hợp với grade_code không
            $gradePrefix = '';
            if (strpos($grade->grade_code, 'G10') === 0) {
                $gradePrefix = '10';
            } elseif (strpos($grade->grade_code, 'G11') === 0) {
                $gradePrefix = '11';
            } elseif (strpos($grade->grade_code, 'G12') === 0) {
                $gradePrefix = '12';
            }

            if (!$gradePrefix || !preg_match("/^{$gradePrefix}[A-Z]$/", $validated['classroom_name'])) {
                return ResponseFormatter::fail(
                    "Tên lớp phải bắt đầu bằng '{$gradePrefix}' và kết thúc bằng một chữ cái in hoa (ví dụ: {$gradePrefix}A, {$gradePrefix}B)",
                    null,
                    400
                );
            }

            $classroom = Classroom::create([
                'classroom_code' => $classroomCode,
                'classroom_name' => $validated['classroom_name'],
                'grade_code' => $validated['grade_code'],
                'student_count' => 0,
                'homeroom_teacher_code' => null,
            ]);

            // Cập nhật classroom_count trong bảng grades
            $grade->classroom_count = Classroom::where('grade_code', $grade->grade_code)->count();
            $grade->save();

            $classroom->load('grade'); // Load thông tin grade sau khi tạo

            return ResponseFormatter::success(
                $classroom,
                'Tạo lớp học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ClassroomController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function show(string $classroom_code): JsonResponse
    {
        try {
            $classroom = Classroom::with('grade')
                ->where('classroom_code', $classroom_code)
                ->first([
                    'classroom_code',
                    'classroom_name',
                    'grade_code',
                    'student_count',
                    'homeroom_teacher_code'
                ]);

            if (!$classroom) {
                return ResponseFormatter::fail(
                    'Lớp học không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $classroom,
                'Lấy thông tin lớp học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ClassroomController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function update(Request $request, string $classroom_code): JsonResponse
    {
        try {
            $classroom = Classroom::where('classroom_code', $classroom_code)->first();

            if (!$classroom) {
                return ResponseFormatter::fail(
                    'Lớp học không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'classroom_name' => 'required|string|max:255|unique:classrooms,classroom_name,' . $classroom->id,
                'grade_code' => 'required|exists:grades,grade_code',
                'homeroom_teacher_code' => 'nullable|exists:teachers,teacher_code', // Đảm bảo homeroom_teacher_code là tùy chọn
            ]);

            $grade = Grade::where('grade_code', $validated['grade_code'])->first();
            if (!$grade) {
                return ResponseFormatter::fail(
                    'Khối không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra classroom_name có phù hợp với grade_code không
            $gradePrefix = '';
            if (strpos($grade->grade_code, 'G10') === 0) {
                $gradePrefix = '10';
            } elseif (strpos($grade->grade_code, 'G11') === 0) {
                $gradePrefix = '11';
            } elseif (strpos($grade->grade_code, 'G12') === 0) {
                $gradePrefix = '12';
            }

            if (!$gradePrefix || !preg_match("/^{$gradePrefix}[A-Z]$/", $validated['classroom_name'])) {
                return ResponseFormatter::fail(
                    "Tên lớp phải bắt đầu bằng '{$gradePrefix}' và kết thúc bằng một chữ cái in hoa (ví dụ: {$gradePrefix}A, {$gradePrefix}B)",
                    null,
                    400
                );
            }

            // Nếu grade_code thay đổi, cần cập nhật classroom_code
            $oldGradeCode = $classroom->grade_code;
            if ($oldGradeCode !== $validated['grade_code']) {
                $classroomCount = Classroom::where('grade_code', $validated['grade_code'])->count() + 1;
                $classroom->classroom_code = "C{$classroomCount}_{$validated['grade_code']}";
            }

            // Xử lý homeroom_teacher_code: nếu không có trong request, giữ nguyên giá trị cũ
            $updateData = [
                'classroom_name' => $validated['classroom_name'],
                'grade_code' => $validated['grade_code'],
            ];

            // Chỉ cập nhật homeroom_teacher_code nếu nó được gửi trong request
            if ($request->has('homeroom_teacher_code')) {
                // Đảm bảo rằng nếu giá trị là chuỗi rỗng, thì gán thành null
                $homeroomTeacherCode = $validated['homeroom_teacher_code'];
                if ($homeroomTeacherCode === '' || is_null($homeroomTeacherCode)) {
                    $updateData['homeroom_teacher_code'] = null;
                } else {
                    $updateData['homeroom_teacher_code'] = $homeroomTeacherCode;
                }
            }

            $classroom->update($updateData);

            // Cập nhật classroom_count cho grade cũ và grade mới (nếu có thay đổi)
            if ($oldGradeCode !== $validated['grade_code']) {
                $oldGrade = Grade::where('grade_code', $oldGradeCode)->first();
                if ($oldGrade) {
                    $oldGrade->classroom_count = Classroom::where('grade_code', $oldGradeCode)->count();
                    $oldGrade->save();
                }
            }
            $grade->classroom_count = Classroom::where('grade_code', $grade->grade_code)->count();
            $grade->save();

            $classroom->load('grade'); // Load thông tin grade sau khi cập nhật

            return ResponseFormatter::success(
                $classroom,
                'Cập nhật lớp học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ClassroomController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    public function destroy(string $classroom_code): JsonResponse
    {
        try {
            $classroom = Classroom::where('classroom_code', $classroom_code)->first();

            if (!$classroom) {
                return ResponseFormatter::fail(
                    'Lớp học không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra nếu lớp học có Student hoặc Teacher liên quan
            if ($classroom->students()->exists() || $classroom->teachers()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa lớp học vì có học sinh hoặc giáo viên liên quan',
                    null,
                    400
                );
            }

            $grade = Grade::where('grade_code', $classroom->grade_code)->first();

            $classroom->delete();

            // Cập nhật classroom_count trong bảng grades
            if ($grade) {
                $grade->classroom_count = Classroom::where('grade_code', $grade->grade_code)->count();
                $grade->save();
            }

            return ResponseFormatter::success(
                null,
                'Xóa lớp học thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in ClassroomController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa lớp học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}