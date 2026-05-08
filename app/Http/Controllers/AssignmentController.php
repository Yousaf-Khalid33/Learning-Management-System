<?php

namespace App\Http\Controllers;

use App\Models\AssignmentSubmission;
use App\Models\Material;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    // 1. STUDENT: Submit Assignment
    public function submit(Request $request, $materialId)
    {
        // UPDATE: Allowed more file types for students
        $request->validate([
            'file' => 'required|file|max:50000|mimes:pdf,doc,docx,zip,png,jpg,jpeg'
        ]);

        $material = Material::findOrFail($materialId);
        if ($material->type !== 'assignment') {
            return response()->json(['message' => 'This material is not an assignment.'], 400);
        }

        $isLate = false;
        $message = 'Assignment submitted successfully!';
        
        if ($material->due_date && now()->greaterThan($material->due_date)) {
            $isLate = true;
            $message = 'Assignment submitted LATE. Your teacher has been notified.';
        }

        $path = $request->file('file')->store('assignments', 'public');

        $submission = AssignmentSubmission::updateOrCreate(
            ['student_id' => $request->user()->id, 'material_id' => $materialId],
            [
                'file_path' => $path,
                'is_late' => $isLate
            ]
        );

        return response()->json(['message' => $message, 'submission' => $submission, 'is_late' => $isLate], 201);
    }

    // 2. TEACHER: View Submissions
    public function index(Request $request, $materialId)
    {
        $material = Material::with('section.course')->findOrFail($materialId);
        if ($request->user()->id !== $material->section->course->teacher_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $submissions = AssignmentSubmission::where('material_id', $materialId)->with('student:id,name,email')->get();
        return response()->json($submissions);
    }

    // 3. TEACHER: Grade
    public function grade(Request $request, $id)
    {
        $submission = AssignmentSubmission::with('material.section.course')->findOrFail($id);
        if ($request->user()->id !== $submission->material->section->course->teacher_id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $submission->update($request->validate(['grade' => 'required|integer|min:0|max:100', 'feedback' => 'nullable|string']));
        return response()->json(['message' => 'Graded successfully', 'submission' => $submission]);
    }

    // 4. STUDENT: My Submission
    public function mySubmission(Request $request, $materialId)
    {
        $submission = AssignmentSubmission::where('student_id', $request->user()->id)->where('material_id', $materialId)->first();
        if (!$submission) return response()->json(['message' => 'No submission found', 'submitted' => false]);
        return response()->json(['submission' => $submission, 'submitted' => true]);
    }
}