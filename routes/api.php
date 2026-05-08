<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\AssignmentController; 

// ==========================
// PUBLIC ROUTES
// ==========================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// ==========================
// PROTECTED ROUTES
// ==========================
// CRITICAL FIX: Changed from 'auth:sanctum' to 'auth:api' for JWT
Route::middleware('auth:api')->group(function () {
    
    // --- USER PROFILE ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']); 
    Route::get('/user', [AuthController::class, 'userProfile']);
    Route::put('/user', [AuthController::class, 'updateProfile']);

    // --- ADMIN MANAGEMENT ---
    Route::get('/admin/stats', [AdminController::class, 'stats']);
    Route::get('/admin/submissions', [AdminController::class, 'submissions']); 
    Route::get('/admin/quizzes', [AdminController::class, 'quizzes']);
    Route::get('/admin/users', [AdminController::class, 'index']);
    Route::post('/admin/users', [AdminController::class, 'store']); 
    Route::put('/admin/users/{id}', [AdminController::class, 'update']);
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroy']);

    // --- COURSES ---
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    
    // --- TEACHER SPECIFIC ---
    Route::get('/teacher/courses', [CourseController::class, 'teacherCourses']); 

    // --- SECTIONS & MATERIALS ---
    Route::post('/courses/{id}/sections', [CourseController::class, 'addSection']);
    Route::put('/sections/{id}', [CourseController::class, 'updateSection']); 
    Route::delete('/sections/{id}', [CourseController::class, 'deleteSection']);
    
    Route::post('/sections/{id}/materials', [CourseController::class, 'addMaterial']);
    Route::put('/materials/{id}', [CourseController::class, 'updateMaterial']); 
    Route::delete('/materials/{id}', [CourseController::class, 'deleteMaterial']);

    // --- ASSIGNMENTS ---
    Route::post('/materials/{id}/submit', [AssignmentController::class, 'submit']); 
    Route::get('/materials/{id}/submissions', [AssignmentController::class, 'index']); 
    Route::post('/submissions/{id}/grade', [AssignmentController::class, 'grade']); 
    Route::get('/materials/{id}/my-submission', [AssignmentController::class, 'mySubmission']);

    // --- ENROLLMENTS ---
    Route::post('/courses/{id}/enroll', [EnrollmentController::class, 'enroll']);
    Route::post('/courses/{id}/unenroll', [EnrollmentController::class, 'unenroll']);
    Route::get('/my-courses', [EnrollmentController::class, 'myCourses']);
    Route::get('/courses/{id}/students', [EnrollmentController::class, 'getCourseStudents']); 

    // --- QUIZZES ---
    Route::post('/quizzes', [QuizController::class, 'createQuiz']);
    Route::put('/quizzes/{id}', [QuizController::class, 'updateQuiz']); 
    Route::delete('/quizzes/{id}', [QuizController::class, 'deleteQuiz']);
    
    Route::post('/quizzes/{id}/questions', [QuizController::class, 'addQuestion']);
    Route::put('/questions/{id}', [QuizController::class, 'updateQuestion']); 
    Route::delete('/questions/{id}', [QuizController::class, 'deleteQuestion']);
    
    Route::get('/quizzes/{id}', [QuizController::class, 'show']);
    Route::post('/quizzes/{id}/submit', [QuizController::class, 'submit']);
    
    Route::get('/quizzes/{id}/attempts', [QuizController::class, 'getQuizAttempts']); 
    Route::get('/my-results', [QuizController::class, 'getMyAttempts']); 
    Route::get('/submissions/{id}', [QuizController::class, 'reviewAttempt']); 

    // --- CERTIFICATES ---
    Route::post('/courses/{id}/certificate', [CertificateController::class, 'claim']);
    Route::get('/my-certificates', [CertificateController::class, 'index']);
    Route::get('/courses/{id}/leaderboard', [LeaderboardController::class, 'courseLeaderboard']);
});