<?php
namespace App\Http\Controllers;

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
        $validator = $this->validateRequest($request, [
            'name' => 'required|min:2',
            'email' => 'required|email|unique:teachers,email|unique:students,email',
            'password' => 'required|min:6|confirmed',
            'role_code' => 'required|in:R1,R2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$this->isValidRoleCode($request->role_code)) {
            return response()->json(['message' => 'Invalid role_code'], 422);
        }

        $user = $this->authService->createUser($request->role_code, $request->all());
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json(['token' => $token], 201);
    }

    public function login(Request $request)
    {
        $validator = $this->validateRequest($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $this->authService->authenticateUser($request->email, $request->password);
        if (!$user) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(null, 204);
    }

    public function user(Request $request)
    {
        $user = $request->user();
        if (!$this->isValidUser($user)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userData = $this->removeSensitiveData($user->toArray());
        return response()->json($userData);
    }

    /**
     * Request a password reset link.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgotPassword(Request $request)
    {
        $validator = $this->validateRequest($request, [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->authService->sendPasswordResetLink($request->email);
        return $success
            ? response()->json(['message' => 'Password reset link sent'], 200)
            : response()->json(['message' => 'Email not found'], 404);
    }

    /**
     * Reset password using token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $email = $request->query('email');
        if (!$email) {
            return response()->json(['message' => 'Email is required in query parameter'], 422);
        }

        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Bearer token is required'], 401);
        }

        $validator = $this->validateRequest($request, [
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $success = $this->authService->resetPassword($email, $token, $request->password);
        return $success
            ? response()->json(['message' => 'Password reset successfully'], 200)
            : response()->json(['message' => 'Invalid token or email'], 400);
    }

    public function validateToken(Request $request)
    {
        $email = $request->query('email');
        if (!$email) {
            return response()->json(['message' => 'Email is required in query parameter'], 422);
        }

        $reset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$reset || now()->subHour()->gt($reset->created_at)) {
            return response()->json(['message' => 'Invalid or expired token'], 400);
        }

        return response()->json(['token' => $reset->token], 200);
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