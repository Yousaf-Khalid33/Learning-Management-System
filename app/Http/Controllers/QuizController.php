<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Course;
use App\Models\Question;
use App\Models\Submission;
use App\Models\SubmissionAnswer;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class QuizController extends Controller
{
    // Helper: Verify Ownership with DEBUG Message
    private function checkPermission($course) {
        $user = auth('api')->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated - Please login again');
        }
        
        // Strict ownership check with Debug Info
        // We use != to allow string '1' to match int 1
        if ($user->id != $course->teacher_id && $user->role !== 'admin') {
            abort(403, "Unauthorized. You (User ID: {$user->id}) are not the owner of this course (Teacher ID: {$course->teacher_id}).");
        }
    }

    // --- TEACHER METHODS ---

    public function createQuiz(Request $request)
    {
        $validated = $request->validate(['course_id' => 'required', 'title' => 'required', 'passing_score' => 'required']);
        $course = Course::findOrFail($validated['course_id']);
        
        $this->checkPermission($course);

        return response()->json(Quiz::create(array_merge($validated, ['is_published' => true])), 201);
    }

    public function updateQuiz(Request $request, $id)
    {
        $quiz = Quiz::with('course')->findOrFail($id);
        $this->checkPermission($quiz->course);

        $data = $request->validate([
            'title' => 'sometimes',
            'passing_score' => 'sometimes',
            'is_published' => 'sometimes', 
            'time_limit' => 'sometimes'
        ]);

        $quiz->fill($data);
        if ($request->has('is_published')) $quiz->is_published = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);
        $quiz->save();

        return response()->json(['message' => 'Quiz updated', 'quiz' => $quiz]);
    }

    public function deleteQuiz(Request $request, $id) 
    {
        $quiz = Quiz::with('course')->findOrFail($id);
        $this->checkPermission($quiz->course);
        $quiz->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function addQuestion(Request $request, $quizId)
    {
        $quiz = Quiz::with('course')->findOrFail($quizId);
        $this->checkPermission($quiz->course);

        $validated = $request->validate(['question_text' => 'required', 'options' => 'required|array|min:2', 'options.*.text' => 'required', 'options.*.is_correct' => 'required']);
        
        $question = $quiz->questions()->create(['question_text' => $validated['question_text'], 'type' => 'mcq']);
        foreach ($validated['options'] as $opt) {
            $question->options()->create(['option_text' => $opt['text'], 'is_correct' => $opt['is_correct']]);
        }
        $quiz->increment('total_questions');
        return response()->json($question, 201);
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = Question::with('quiz.course')->findOrFail($id);
        $this->checkPermission($question->quiz->course);

        $validated = $request->validate(['question_text' => 'sometimes', 'options' => 'sometimes']);
        if ($request->has('question_text')) $question->update(['question_text' => $validated['question_text']]);
        if ($request->has('options')) {
            $question->options()->delete();
            foreach ($validated['options'] as $opt) $question->options()->create(['option_text' => $opt['text'], 'is_correct' => $opt['is_correct']]);
        }
        return response()->json($question);
    }

    public function deleteQuestion(Request $request, $id)
    {
        $q = Question::with('quiz.course')->findOrFail($id);
        $this->checkPermission($q->quiz->course);
        
        $q->quiz->decrement('total_questions');
        $q->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function getQuizAttempts(Request $request, $id)
    {
        $quiz = Quiz::with('course')->findOrFail($id);
        $this->checkPermission($quiz->course);
        return response()->json(Submission::where('quiz_id', $id)->with('student:id,name')->orderByDesc('score')->get());
    }

    // --- STUDENT METHODS ---

    public function show(Request $request, $id)
    {
        $quiz = Quiz::with(['questions.options' => function($query) { $query->select('id', 'question_id', 'option_text'); }])->findOrFail($id);
        $user = auth('api')->user();

        if ($user->role === 'student') {
            $previous = Submission::where('student_id', $user->id)->where('quiz_id', $id)->first();
            
            if ($previous && ($previous->status === 'completed' || $previous->status === 'expired')) {
                return response()->json(['already_attempted' => true, 'score' => $previous->score]);
            }
            if (!$previous) {
                $previous = Submission::create(['student_id' => $user->id, 'quiz_id' => $id, 'score' => 0, 'started_at' => now(), 'status' => 'in_progress']);
            }
            if ($quiz->time_limit && $quiz->time_limit > 0) {
                $startTime = $previous->started_at ? Carbon::parse($previous->started_at) : now();
                $expireTime = $startTime->copy()->addMinutes($quiz->time_limit);
                
                if (now() > $expireTime) { $previous->update(['status' => 'expired']); return response()->json(['message' => 'Time expired.'], 403); }
                $quiz->setAttribute('remaining_seconds', now()->diffInSeconds($expireTime, false));
            } else {
                $quiz->setAttribute('remaining_seconds', null);
            }
        }
        return response()->json($quiz);
    }

    public function getMyAttempts(Request $request)
    {
        return response()->json(Submission::where('student_id', auth('api')->id())->with('quiz:id,title')->orderByDesc('created_at')->get());
    }

    public function reviewAttempt(Request $request, $id)
    {
        $submission = Submission::with(['quiz', 'answers.question.options'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role === 'student' && $submission->student_id !== $user->id) return response()->json(['message' => 'Unauthorized'], 403);
        return response()->json($submission);
    }

    public function submit(Request $request, $quizId)
    {
        $quiz = Quiz::with('questions.options')->findOrFail($quizId);
        $user = auth('api')->user();

        if ($user->role === 'student') {
            if (Submission::where('student_id', $user->id)->where('quiz_id', $quizId)->whereIn('status', ['completed', 'expired'])->exists()) return response()->json(['message' => 'Already attempted.'], 403);
        }

        $validated = $request->validate(['answers' => 'present|array']);
        
        $submission = Submission::firstOrCreate(
            ['student_id' => $user->id, 'quiz_id' => $quizId],
            ['score' => 0, 'started_at' => now(), 'status' => 'in_progress']
        );

        if ($quiz->time_limit && $quiz->time_limit > 0) {
            $startTime = $submission->started_at ? Carbon::parse($submission->started_at) : now();
            $expireTime = $startTime->copy()->addMinutes($quiz->time_limit)->addMinutes(1);
            if (now() > $expireTime) { $submission->update(['status' => 'expired']); return response()->json(['message' => 'Time Limit Exceeded.'], 403); }
        }

        $totalQuestions = $quiz->questions->count();
        $correctAnswers = 0;

        if (!empty($validated['answers'])) {
            foreach ($validated['answers'] as $ans) {
                $q = $quiz->questions->find($ans['question_id']);
                if(!$q) continue;
                $opt = $q->options->find($ans['selected_option_id']);
                $text = $opt ? $opt->option_text : 'Invalid';
                if ($opt && $opt->is_correct) $correctAnswers++;
                SubmissionAnswer::create(['submission_id' => $submission->id, 'question_id' => $q->id, 'student_answer' => $text]);
            }
        }

        $score = ($totalQuestions > 0) ? round(($correctAnswers / $totalQuestions) * 100) : 0;
        $submission->update(['score' => $score, 'status' => 'completed', 'submitted_at' => now()]);
        
        $total = Submission::where('student_id', $user->id)->select('quiz_id', DB::raw('MAX(score) as max_score'))->groupBy('quiz_id')->get()->sum('max_score');
        Leaderboard::updateOrCreate(['student_id' => $user->id], ['total_score' => $total]);

        return response()->json(['score' => $score]);
    }
}