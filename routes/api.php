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
use App\Http\Controllers\StudentController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\AcademicPerformanceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\Auth\GoogleController;
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
    Route::post('/teacher/enter-scores', [TeacherController::class, 'enterScores']);
    Route::put('/teacher/update', [TeacherController::class, 'update']);
    Route::post('/teacher/classroom-scores', [TeacherController::class, 'getClassroomScores']);

    Route::post('/student/scores', [StudentController::class, 'getScores']);
    Route::put('/student/update', [StudentController::class, 'update']);
    Route::post('/export/student-scores', [ExportController::class, 'exportStudentScores']);

    // CRUD routes for SchoolYear
    Route::prefix('school-years')->middleware('teacher')->group(function () {
        Route::post('/', [SchoolYearController::class, 'store']);
        Route::get('/{school_year_code}', [SchoolYearController::class, 'show']);
        Route::put('/{school_year_code}', [SchoolYearController::class, 'update']);
        Route::delete('/{school_year_code}', [SchoolYearController::class, 'destroy']);
    });

    // CRUD routes for Grade
    Route::prefix('grades')->middleware('teacher')->group(function () {
        Route::post('/', [GradeController::class, 'store']);
        Route::get('/{grade_code}', [GradeController::class, 'show']);
        Route::put('/{grade_code}', [GradeController::class, 'update']);
        Route::delete('/{grade_code}', [GradeController::class, 'destroy']);
    });

    // CRUD routes for Subject
    Route::prefix('subjects')->middleware('teacher')->group(function () {
        Route::post('/', [SubjectController::class, 'store']);
        Route::get('/{subject_code}', [SubjectController::class, 'show']);
        Route::put('/{subject_code}', [SubjectController::class, 'update']);
        Route::delete('/{subject_code}', [SubjectController::class, 'destroy']);
    });

    // CRUD routes for Classroom
    Route::prefix('classrooms')->middleware('teacher')->group(function () {
        Route::post('/', [ClassroomController::class, 'store']);
        Route::get('/{classroom_code}', [ClassroomController::class, 'show']);
        Route::put('/{classroom_code}', [ClassroomController::class, 'update']);
        Route::delete('/{classroom_code}', [ClassroomController::class, 'destroy']);
    });

    // CRUD routes for Term
    Route::prefix('terms')->middleware('teacher')->group(function () {
        Route::post('/', [TermController::class, 'store']);
        Route::get('/{term_code}', [TermController::class, 'show']);
        Route::put('/{term_code}', [TermController::class, 'update']);
        Route::delete('/{term_code}', [TermController::class, 'destroy']);
    });

    // CRUD routes for Exam
    Route::prefix('exams')->middleware('teacher')->group(function () {
        Route::post('/', [ExamController::class, 'store']);
        Route::get('/{exam_code}', [ExamController::class, 'show']);
        Route::put('/{exam_code}', [ExamController::class, 'update']);
        Route::delete('/{exam_code}', [ExamController::class, 'destroy']);
    });
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

Route::prefix('rankings')->group(function () {
    // Routes cho xếp hạng cả năm
    Route::post('/classroom-yearly', [RankingController::class, 'getClassroomYearlyRankings']);
    Route::post('/grade-yearly', [RankingController::class, 'getGradeYearlyRankings']);

    // Routes cho xếp hạng học kỳ
    Route::post('/classroom-term', [RankingController::class, 'getClassroomTermRankings']);
    Route::post('/grade-term', [RankingController::class, 'getGradeTermRankings']);
});

Route::prefix('academic-performance')->group(function () {
    // Routes cho học lực trong lớp
    Route::post('/classroom-term', [AcademicPerformanceController::class, 'getClassroomTermPerformance']);
    Route::post('/classroom-yearly', [AcademicPerformanceController::class, 'getClassroomYearlyPerformance']);

    // Routes cho học lực trong khối
    Route::post('/grade-term', [AcademicPerformanceController::class, 'getGradeTermPerformance']);
    Route::post('/grade-yearly', [AcademicPerformanceController::class, 'getGradeYearlyPerformance']);
});

Route::get('/export-scores', [ExportController::class, 'exportScores']);
Route::get('/export-student-term-averages', [ExportController::class, 'exportStudentTermAverages']);
Route::get('/export-student-yearly-averages', [ExportController::class, 'exportStudentYearlyAverages']);

Route::post('/import-scores', [ImportController::class, 'importScores']);

Route::post('/ai/ask', [AIController::class, 'ask']);