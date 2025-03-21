<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    /**
     * Lấy danh sách tất cả các role.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $roles = Role::all(['role_code', 'role_name']);
            return ResponseFormatter::success(
                $roles,
                'Lấy danh sách role thành công'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::fail(
                'Không thể lấy danh sách role: ' . $e->getMessage(),
                null,
                500
            );
        }
    }
}