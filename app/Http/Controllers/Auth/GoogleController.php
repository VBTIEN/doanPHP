<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Services\AuthService;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            \Log::info('Redirecting to Google - Config Check', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect' => config('services.google.redirect'),
            ]);

            if (!config('services.google.client_id') || !config('services.google.client_secret') || !config('services.google.redirect')) {
                throw new \Exception('Cấu hình Google chưa đầy đủ');
            }

            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Exception $e) {
            \Log::error('Google Redirect Error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể khởi tạo Google Auth: ' . $e->getMessage()], 500);
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            \Log::info('Handling Google callback');
            \Log::info('Request data: ', $request->all());

            $code = $request->input('code');
            if (!$code) {
                \Log::warning('No code provided in Google callback');
                return response()->json(['error' => 'Mã xác thực không được cung cấp'], 400);
            }

            // Cấu hình Guzzle để bỏ qua SSL (chỉ dùng cho dev)
            $client = new \GuzzleHttp\Client(['verify' => false]);
            \Log::info('Attempting to get user from Google with code: ' . $code);

            Socialite::driver('google')->setHttpClient($client);

            $googleUser = Socialite::driver('google')->stateless()->user();

            \Log::info('Google User Data: ', (array) $googleUser);

            if (!$googleUser || !isset($googleUser->email)) {
                \Log::warning('Google user data is null or missing email');
                return response()->json(['error' => 'Không thể lấy thông tin từ Google'], 400);
            }

            $student = Student::where('email', $googleUser->email)->first();
            $teacher = Teacher::where('email', $googleUser->email)->first();

            if ($student) {
                $token = $student->createToken('auth_token')->plainTextToken;
                return response()->json(['token' => $token, 'user' => $student]);
            }

            if ($teacher) {
                $token = $teacher->createToken('auth_token')->plainTextToken;
                return response()->json(['token' => $token, 'user' => $teacher]);
            }

            return response()->json([
                'message' => 'Chọn vai trò',
                'user_data' => [
                    'google_id' => $googleUser->id,
                    'email' => $googleUser->email,
                    'name' => $googleUser->name,
                ]
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            \Log::error('Google Login Error: ' . $errorMessage);
            \Log::error('Stack Trace: ' . $e->getTraceAsString());

            // Kiểm tra nếu lỗi là invalid_grant
            if (str_contains($errorMessage, 'invalid_grant')) {
                return response()->json(['error' => 'Mã xác thực không hợp lệ hoặc đã được sử dụng'], 400);
            }

            return response()->json(['error' => 'Lỗi máy chủ: ' . $errorMessage], 500);
        }
    }

    public function showRoleSelectionForm()
    {
        return response()->json(['message' => 'Chọn vai trò', 'options' => ['student', 'teacher']]);
    }

    public function handleRoleSelection(Request $request)
    {
        $request->validate(['role' => 'required|in:student,teacher']);
        $userData = $request->input('user_data');

        if (!$userData || !isset($userData['email'])) {
            return response()->json(['error' => 'Thông tin người dùng không hợp lệ'], 400);
        }

        $authService = new AuthService();
        $roleCode = $request->input('role') === 'student' ? 'R2' : 'R1';
        $code = $authService->generateCode($roleCode);

        $randomPassword = Str::random(16);
        $model = $request->input('role') === 'student' ? new Student() : new Teacher();

        $user = $model->create([
            'student_code' => $request->input('role') === 'student' ? $code : null,
            'teacher_code' => $request->input('role') === 'teacher' ? $code : null,
            'email' => $userData['email'],
            'password' => bcrypt($randomPassword),
            'name' => $userData['name'],
            'role_code' => $roleCode,
            'google_id' => $userData['google_id'],
            'email_verified_at' => now(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }
}