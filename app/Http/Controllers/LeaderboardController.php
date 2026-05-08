<?php

namespace App\Http\Controllers;

use App\Models\Leaderboard;
use App\Models\Submission;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    // 1. GLOBAL LEADERBOARD (All time)
    public function index()
    {
        $leaders = Leaderboard::with('student:id,name')
                              ->orderByDesc('total_score')
                              ->take(10)
                              ->get();
        return response()->json($leaders);
    }

    // 2. COURSE LEADERBOARD (New Requirement)
    public function courseLeaderboard($courseId)
    {
        // 1. Get all quizzes in this course
        $course = Course::findOrFail($courseId);
        $quizIds = $course->quizzes()->pluck('id');

        // 2. Sum scores for these quizzes grouped by student
        $leaders = Submission::whereIn('quiz_id', $quizIds)
            ->select('student_id', DB::raw('SUM(score) as total_score'))
            ->groupBy('student_id')
            ->orderByDesc('total_score')
            ->with('student:id,name')
            ->take(10)
            ->get();

        return response()->json($leaders);
    }
}