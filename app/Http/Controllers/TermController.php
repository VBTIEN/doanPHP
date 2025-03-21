<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Term;
use Illuminate\Http\JsonResponse;

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
            $terms = Term::all([
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
            return ResponseFormatter::fail(
                'Không thể lấy danh sách học kỳ: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}