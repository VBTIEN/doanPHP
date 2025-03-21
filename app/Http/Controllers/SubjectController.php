<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;

class SubjectController extends Controller
{
    /**
     * Lấy danh sách tất cả các môn học.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $subjects = Subject::all(['subject_code', 'subject_name']);
            return ResponseFormatter::success(
                $subjects,
                'Lấy danh sách môn học thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách môn học: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}