<?php

use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::get('/students-by-classroom', [TeacherController::class, 'getStudentsByClassroom']);
    Route::post('/assign-homeroom-classroom', [TeacherController::class, 'assignHomeroomClassroom']);
    Route::post('/assign-teaching-classroom', [TeacherController::class, 'assignTeachingClassroom']);
});

Route::middleware('cors')->group(function () {
    Route::get('/status', function () {
        return response()->json(['status' => 'ok', 'backend' => 'php']);
    });
    Route::get('/password/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);
});

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('/select-role', [GoogleController::class, 'showRoleSelectionForm']);
Route::post('/select-role', [GoogleController::class, 'handleRoleSelection']);

Route::get('/roles', [RoleController::class, 'index']);
Route::get('/school-years', [SchoolYearController::class, 'index']);
Route::get('/classrooms', [ClassroomController::class, 'index']);
Route::get('/exams', [ExamController::class, 'index']);
Route::get('/grades', [GradeController::class, 'index']);
Route::get('/subjects', [SubjectController::class, 'index']);
Route::get('/terms', [TermController::class, 'index']);

Route::get('/teachers-in-classroom', [TeacherController::class, 'getTeachersInClassroom']);