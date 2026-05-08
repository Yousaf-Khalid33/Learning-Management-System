<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\Submission;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function claim(Request $request, $courseId)
    {
        $user = $request->user();

        // 1. Enrollment Check
        if (!Enrollment::where('student_id', $user->id)->where('course_id', $courseId)->exists()) {
            return response()->json(['message' => 'You must be enrolled to claim a certificate.'], 403);
        }

        // 2. GET QUIZ DATA
        $quizzes = Quiz::where('course_id', $courseId)->where('is_published', true)->get();
        $totalQuizzes = $quizzes->count();

        if ($totalQuizzes === 0) {
             return response()->json(['message' => 'No quizzes available to complete.'], 403);
        }

        // 3. CHECK COMPLETION
        $quizIds = $quizzes->pluck('id');
        $submissions = Submission::where('student_id', $user->id)
                                 ->whereIn('quiz_id', $quizIds)
                                 ->get();

        $attemptedQuizIds = $submissions->pluck('quiz_id')->unique();
        
        if ($attemptedQuizIds->count() < $totalQuizzes) {
            $remaining = $totalQuizzes - $attemptedQuizIds->count();
            return response()->json(['message' => "You have {$remaining} quizzes left to complete. You must attempt all quizzes."], 403);
        }

        // 4. CALCULATE REAL-TIME GRADE
        $totalObtained = 0;
        $totalPossible = 0;

        foreach ($quizzes as $quiz) {
            // FIX: Scores in DB are Percentages (0-100), so Max is always 100
            $quizMax = 100; 
            
            // Get best score for this quiz
            $sub = $submissions->where('quiz_id', $quiz->id)->sortByDesc('score')->first();
            $score = $sub ? $sub->score : 0;

            $totalObtained += $score;
            $totalPossible += $quizMax;
        }

        $percentage = ($totalPossible > 0) ? ($totalObtained / $totalPossible) * 100 : 0;
        $percentage = round($percentage, 1);

        // 5. FAIL CHECK
        if ($percentage < 50) {
            return response()->json([
                'message' => "You failed: better luck next time. (Score: {$percentage}%)"
            ], 403);
        }
        
        // Determine Letter Grade
        $grade = 'F';
        if ($percentage >= 90) $grade = 'A1';
        elseif ($percentage >= 80) $grade = 'A';
        elseif ($percentage >= 70) $grade = 'B';
        elseif ($percentage >= 60) $grade = 'C';
        elseif ($percentage >= 50) $grade = 'D';

        // 6. GENERATE PDF
        $data = [
            'student_name' => $user->name,
            'course_title' => \App\Models\Course::find($courseId)->title,
            'date' => now()->toFormattedDateString(),
            'grade' => $grade,
            'percentage' => $percentage,
            'certificate_id' => uniqid()
        ];

        $pdf = Pdf::loadView('pdf.certificate', $data);
        $filename = 'certificates/cert_' . $user->id . '_' . $courseId . '.pdf';
        
        Storage::disk('public')->put($filename, $pdf->output());

        // 7. Save Record
        $cert = Certificate::updateOrCreate(
            ['student_id' => $user->id, 'course_id' => $courseId],
            [
                'issue_date' => now(),
                'pdf_path' => $filename
            ]
        );
        
        $cert->load('student', 'course');

        return response()->json(['message' => 'Certificate generated successfully!', 'certificate' => $cert], 201);
    }

    public function index(Request $request)
    {
        return response()->json($request->user()->certificates()->with('course')->get());
    }
}