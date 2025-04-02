<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TermController extends Controller
{
    /**
     * Lấy danh sách tất cả các học kỳ.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $terms = Term::with('schoolYear')->get([
                'term_code',
                'term_name',
                'start_date',
                'end_date',
                'school_year_code'
            ]);
            return ResponseFormatter::success(
                $terms,
                'Lấy danh sách học kỳ thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in TermController@index: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy danh sách học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Tạo một học kỳ mới.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'term_name' => 'required|string|max:255|unique:terms,term_name',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            $term = Term::create([
                'term_code' => 'T' . (Term::count() + 1),
                'term_name' => $validated['term_name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'school_year_code' => $validated['school_year_code'],
            ]);

            $term->load('schoolYear'); // Load thông tin schoolYear sau khi tạo

            return ResponseFormatter::success(
                $term,
                'Tạo học kỳ thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in TermController@store: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể tạo học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Lấy thông tin chi tiết của một học kỳ.
     *
     * @param string $term_code
     * @return JsonResponse
     */
    public function show(string $term_code): JsonResponse
    {
        try {
            $term = Term::with('schoolYear')
                ->where('term_code', $term_code)
                ->first([
                    'term_code',
                    'term_name',
                    'start_date',
                    'end_date',
                    'school_year_code'
                ]);

            if (!$term) {
                return ResponseFormatter::fail(
                    'Học kỳ không tồn tại',
                    null,
                    404
                );
            }

            return ResponseFormatter::success(
                $term,
                'Lấy thông tin học kỳ thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in TermController@show: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể lấy thông tin học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Cập nhật thông tin một học kỳ.
     *
     * @param Request $request
     * @param string $term_code
     * @return JsonResponse
     */
    public function update(Request $request, string $term_code): JsonResponse
    {
        try {
            $term = Term::where('term_code', $term_code)->first();

            if (!$term) {
                return ResponseFormatter::fail(
                    'Học kỳ không tồn tại',
                    null,
                    404
                );
            }

            $validated = $request->validate([
                'term_name' => 'required|string|max:255|unique:terms,term_name,' . $term->id,
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'school_year_code' => 'required|exists:school_years,school_year_code',
            ]);

            $term->update([
                'term_name' => $validated['term_name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'school_year_code' => $validated['school_year_code'],
            ]);

            $term->load('schoolYear'); // Load thông tin schoolYear sau khi cập nhật

            return ResponseFormatter::success(
                $term,
                'Cập nhật học kỳ thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in TermController@update: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể cập nhật học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }

    /**
     * Xóa một học kỳ.
     *
     * @param string $term_code
     * @return JsonResponse
     */
    public function destroy(string $term_code): JsonResponse
    {
        try {
            $term = Term::where('term_code', $term_code)->first();

            if (!$term) {
                return ResponseFormatter::fail(
                    'Học kỳ không tồn tại',
                    null,
                    404
                );
            }

            // Kiểm tra xem học kỳ có điểm số liên quan không
            if ($term->scores()->exists()) {
                return ResponseFormatter::fail(
                    'Không thể xóa học kỳ vì đã có điểm số liên quan',
                    null,
                    400
                );
            }

            $term->delete();

            return ResponseFormatter::success(
                null,
                'Xóa học kỳ thành công'
            );
        } catch (\Exception $e) {
            Log::error('Error in TermController@destroy: ' . $e->getMessage());
            return ResponseFormatter::fail(
                'Không thể xóa học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}