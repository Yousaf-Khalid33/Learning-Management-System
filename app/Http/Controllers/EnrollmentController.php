<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    // Enroll (Student)
    public function enroll(Request $request, $courseId)
    {
        $user = $request->user();
        
        if (Enrollment::where('student_id', $user->id)->where('course_id', $courseId)->exists()) {
            return response()->json(['message' => 'Already enrolled'], 409);
        }

        Enrollment::create(['student_id' => $user->id, 'course_id' => $courseId]);
        return response()->json(['message' => 'Enrolled successfully'], 201);
    }

    // Unenroll (Student)
    public function unenroll(Request $request, $courseId)
    {
        $deleted = Enrollment::where('student_id', $request->user()->id)
                             ->where('course_id', $courseId)
                             ->delete();
        
        if ($deleted) return response()->json(['message' => 'Unenrolled successfully']);
        return response()->json(['message' => 'Not enrolled'], 404);
    }

    // Get My Courses (Student)
    public function myCourses(Request $request)
    {
        return response()->json($request->user()->enrollments()->with('course.teacher:id,name')->get());
    }

    // Get Students in My Course (Teacher/Admin View)
    public function getCourseStudents(Request $request, $courseId)
    {
        $course = Course::with('sections.materials')->findOrFail($courseId);

        // SECURITY: Only Course Owner or Admin
        if ($request->user()->id !== $course->teacher_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to student data.'], 403);
        }
        
        // 1. Get Quiz IDs for filtering
        $courseQuizIds = $course->quizzes()->pluck('id');

        // 2. Get Assignment IDs for filtering
        // (We need to find which materials in this course are assignments)
        $courseAssignmentIds = $course->sections->flatMap(function($section) {
            return $section->materials->where('type', 'assignment')->pluck('id');
        });

        $enrollments = Enrollment::where('course_id', $courseId)
            ->with(['student' => function($query) use ($courseQuizIds, $courseAssignmentIds) {
                $query->select('id', 'name', 'email')
                      // Fetch Quiz Scores
                      ->with(['submissions' => function($subQuery) use ($courseQuizIds) {
                          $subQuery->whereIn('quiz_id', $courseQuizIds)
                                   ->select('id', 'student_id', 'quiz_id', 'score', 'created_at', 'status');
                      }])
                      // Fetch Assignment Submissions (NEW)
                      ->with(['assignmentSubmissions' => function($subQuery) use ($courseAssignmentIds) {
                          $subQuery->whereIn('material_id', $courseAssignmentIds)
                                   ->select('id', 'student_id', 'material_id', 'grade', 'is_late', 'created_at');
                      }]);
            }])
            ->get();

        // Flatten the data for easier frontend display
        $data = $enrollments->map(function($enrollment) {
            return [
                'student_id' => $enrollment->student->id,
                'name' => $enrollment->student->name,
                'email' => $enrollment->student->email,
                'enrolled_at' => $enrollment->created_at,
                'quiz_results' => $enrollment->student->submissions,
                'assignment_results' => $enrollment->student->assignmentSubmissions // Now included!
            ];
        });

        return response()->json($data);
    }
}