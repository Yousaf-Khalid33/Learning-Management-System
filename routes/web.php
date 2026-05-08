<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Auth
Route::view('/', 'login')->name('login');
Route::view('/register', 'register');

// 2. Dashboards
Route::view('/dashboard', 'dashboard');         // Admin
Route::view('/teacher-dashboard', 'teacher');   // Teacher
Route::view('/student-dashboard', 'student');   // Student

// 3. Course Pages
Route::get('/course-manager/{id}', function ($id) {
    return view('course_manager', ['id' => $id]);
});

Route::get('/quiz-builder/{id}', function ($id) {
    return view('quiz_builder', ['id' => $id]);
});

// NEW: Assignment Grader
Route::get('/assignment-grader/{id}', function ($id) {
    return view('assignment_grader', ['id' => $id]);
});

// 4. Student Pages
Route::get('/student-course/{id}', function ($id) {
    return view('student_course', ['id' => $id]);
});

Route::get('/take-quiz/{id}', function ($id) {
    return view('quiz_taker', ['id' => $id]);
});