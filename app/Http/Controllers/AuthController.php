<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Role;
use App\Services\AuthService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:teachers,email|unique:students,email',
            'password' => 'required|min:6|confirmed',
            'role_code' => 'required|in:R1,R2',
        ];

        if ($request->role_code === 'R1') {
            $rules['subject_codes'] = 'required|array|min:1';
            $rules['subject_codes.*'] = 'string|exists:subjects,subject_code';
            // Sửa rule cho classroom_code: không cho phép chuỗi rỗng
            $rules['classroom_code'] = 'nullable|string|min:1|exists:classrooms,classroom_code';
        } elseif ($request->role_code === 'R2') {
            $rules['grade_code'] = 'required|string|exists:grades,grade_code';
        }

        $validator = $this->validateRequest($request, $rules);

        if ($validator->fails()) {
            return ResponseFormatter::fail(
                'Dữ liệu không hợp lệ',
                $validator->errors(),
                422
            );
        }

        if (!$this->isValidRoleCode($request->role_code)) {
            return ResponseFormatter::fail(
                'role_code không hợp lệ',
                null,
                422
            );
        }

        $user = $this->authService->createUser($request->role_code, $request->all());
        $token = $user->createToken('auth-token')->plainTextToken;

        return ResponseFormatter::success(
            ['token' => $token, 'user' => $user],
            'Đăng ký thành công',
            201
        );
    }

    public function login(Request $request)
    {
        $validator = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::fail(
                'Dữ liệu không hợp lệ',
                $validator->errors(),
                422
            );
        }

        $user = $this->authService->authenticateUser($request->email, $request->password);
        if (!$user) {
            return ResponseFormatter::fail(
                'Thông tin đăng nhập không hợp lệ',
                null,
                401
            );
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        return ResponseFormatter::success(
            ['token' => $token],
            'Đăng nhập thành công'
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success(
            null,
            'Đăng xuất thành công',
            204
        );
    }

    public function user(Request $request)
    {
        $user = $request->user();
        if (!$this->isValidUser($user)) {
            return ResponseFormatter::fail(
                'Không có quyền truy cập',
                null,
                401
            );
        }

        $userData = $this->removeSensitiveData($user->toArray());
        return ResponseFormatter::success(
            $userData,
            'Lấy thông tin người dùng thành công'
        );
    }

    public function forgotPassword(Request $request)
    {
        $validator = $this->validateRequest($request, [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::fail(
                'Dữ liệu không hợp lệ',
                $validator->errors(),
                422
            );
        }

        $success = $this->authService->sendPasswordResetLink($request->email);
        return $success
            ? ResponseFormatter::success(
                null,
                'Gửi liên kết đặt lại mật khẩu thành công',
                200
            )
            : ResponseFormatter::fail(
                'Không tìm thấy email',
                null,
                404
            );
    }

    public function resetPassword(Request $request)
    {
        $email = $request->query('email');
        if (!$email) {
            return ResponseFormatter::fail(
                'Email là bắt buộc trong query parameter',
                null,
                422
            );
        }

        $token = $request->bearerToken();
        if (!$token) {
            return ResponseFormatter::fail(
                'Bearer token là bắt buộc',
                null,
                401
            );
        }

        $validator = $this->validateRequest($request, [
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::fail(
                'Dữ liệu không hợp lệ',
                $validator->errors(),
                422
            );
        }

        $success = $this->authService->resetPassword($email, $token, $request->password);
        return $success
            ? ResponseFormatter::success(
                null,
                'Đặt lại mật khẩu thành công',
                200
            )
            : ResponseFormatter::fail(
                'Token hoặc email không hợp lệ',
                null,
                400
            );
    }

    public function validateToken(Request $request)
    {
        $email = $request->query('email');
        if (!$email) {
            return ResponseFormatter::fail(
                'Email là bắt buộc trong query parameter',
                null,
                422
            );
        }

        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$reset || now()->subHour()->gt($reset->created_at)) {
            return ResponseFormatter::fail(
                'Token không hợp lệ hoặc đã hết hạn',
                null,
                400
            );
        }

        return ResponseFormatter::success(
            ['token' => $reset->token],
            'Token hợp lệ'
        );
    }

    private function validateRequest(Request $request, array $rules)
    {
        return Validator::make($request->all(), $rules);
    }

    private function isValidRoleCode($roleCode)
    {
        return Role::where('role_code', $roleCode)->exists();
    }

    private function isValidUser($user)
    {
        return $user instanceof Teacher || $user instanceof Student;
    }

    private function removeSensitiveData(array $data)
    {
        unset($data['password']);
        return $data;
    }
}