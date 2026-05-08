<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Section;
use App\Models\Material;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    // --- PUBLIC ---
    public function index()
    {
        return response()->json(Course::with('teacher:id,name')->get());
    }

    public function show(Request $request, $id)
    {
        $course = Course::with(['sections.materials', 'quizzes', 'teacher:id,name'])->findOrFail($id);
        
        $user = $request->user();

        // 1. Admin & Owner Access
        if ($user->role === 'admin' || $user->id === $course->teacher_id) {
            return response()->json($course);
        }

        // 2. Student Access
        if ($user->role === 'student') {
            $isEnrolled = Enrollment::where('student_id', $user->id)->where('course_id', $course->id)->exists();
            if (!$isEnrolled) return response()->json(['message' => 'Access Denied. Please enroll first.'], 403);

            // FIX: Load this student's submissions for these quizzes
            // This allows the frontend to see if they attempted it and what the score is.
            $course->load(['quizzes.submissions' => function($query) use ($user) {
                $query->where('student_id', $user->id)->latest();
            }]);
        }

        return response()->json($course);
    }

    // --- TEACHER SPECIFIC ---
    public function teacherCourses(Request $request)
    {
        $courses = Course::where('teacher_id', $request->user()->id)->withCount(['sections', 'enrollments'])->get();
        return response()->json($courses);
    }

    // --- ADMIN ---
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $course = Course::create($request->validate(['title'=>'required','description'=>'required','teacher_id'=>'required']));
        return response()->json(['message' => 'Course created', 'course' => $course], 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        Course::findOrFail($id)->update($request->validate(['title'=>'sometimes','description'=>'sometimes','teacher_id'=>'sometimes']));
        return response()->json(['message' => 'Course updated']);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        Course::findOrFail($id)->delete();
        return response()->json(['message' => 'Course deleted']);
    }

    // --- SECTIONS ---
    public function addSection(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        if ($request->user()->id !== $course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $section = $course->sections()->create($request->validate(['title' => 'required', 'order' => 'required']));
        return response()->json($section, 201);
    }

    public function updateSection(Request $request, $id)
    {
        $section = Section::findOrFail($id);
        if ($request->user()->id !== $section->course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $section->update($request->validate(['title' => 'sometimes', 'order' => 'sometimes']));
        return response()->json($section);
    }

    public function deleteSection(Request $request, $id)
    {
        $section = Section::findOrFail($id);
        if ($request->user()->id !== $section->course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $section->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // --- MATERIALS ---
    public function addMaterial(Request $request, $sectionId)
    {
        $section = Section::with('course')->findOrFail($sectionId);
        if ($request->user()->id !== $section->course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        
        $validated = $request->validate([
            'title' => 'required|string',
            'type' => 'required|in:pdf,video,text,assignment,document,image,archive', 
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:50000',
            'due_date' => 'nullable|date'
        ]);

        $filePath = $request->hasFile('file') ? $request->file('file')->store('materials', 'public') : null;

        $material = $section->materials()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'content' => $validated['content'],
            'file_path' => $filePath,
            'due_date' => $request->due_date ?? null
        ]);

        return response()->json($material, 201);
    }

    public function updateMaterial(Request $request, $id)
    {
        $material = Material::with('section.course')->findOrFail($id);
        if ($request->user()->id !== $material->section->course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $material->update($request->validate(['title' => 'sometimes', 'content' => 'nullable', 'due_date' => 'nullable|date']));
        return response()->json($material);
    }

    public function deleteMaterial(Request $request, $id)
    {
        $material = Material::with('section.course')->findOrFail($id);
        if ($request->user()->id !== $material->section->course->teacher_id && $request->user()->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);
        $material->delete();
        return response()->json(['message' => 'Deleted']);
    }
}