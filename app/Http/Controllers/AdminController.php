<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // HELPER: Ensure User is Admin
    private function checkAdmin($user) {
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized. Admin access required.');
        }
    }

    // 1. GET ALL USERS
    public function index(Request $request)
    {
        $this->checkAdmin($request->user());

        $query = User::query();
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }
        return response()->json($query->get());
    }

    // 2. CREATE USER
    public function store(Request $request)
    {
        $this->checkAdmin($request->user());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,teacher,student',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
    }

    // 3. UPDATE USER
    public function update(Request $request, $id)
    {
        $this->checkAdmin($request->user());

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'nullable|string|min:6',
            'role' => 'sometimes|in:admin,teacher,student',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->has('name')) $user->name = $validated['name'];
        if ($request->has('email')) $user->email = $validated['email'];
        if ($request->has('role')) $user->role = $validated['role'];
        if ($request->has('is_active')) $user->is_active = $validated['is_active'];

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        return response()->json(['message' => 'User updated successfully', 'user' => $user]);
    }

    // 4. DELETE USER
    public function destroy(Request $request, $id)
    {
        $this->checkAdmin($request->user());

        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    // 5. SYSTEM STATS
    public function stats(Request $request)
    {
        $this->checkAdmin($request->user());

        return response()->json([
            'total_students' => User::where('role', 'student')->count(),
            'total_teachers' => User::where('role', 'teacher')->count(),
            'total_courses' => Course::count(),
            'total_quizzes' => Quiz::count(),
            'total_submissions' => Submission::count(),
        ]);
    }

    // 6. GET ALL SUBMISSIONS
    public function submissions(Request $request)
    {
        $this->checkAdmin($request->user());

        $submissions = Submission::with(['student:id,name,email', 'quiz:id,title,course_id', 'quiz.course:id,title'])
                                 ->orderByDesc('created_at')
                                 ->take(50)
                                 ->get();
        return response()->json($submissions);
    }

    // 7. GET ALL QUIZZES (New Requirement)
    public function quizzes(Request $request)
    {
        $this->checkAdmin($request->user());

        // List all quizzes with their course and teacher name
        $quizzes = Quiz::with(['course:id,title,teacher_id', 'course.teacher:id,name'])
                       ->get();
        return response()->json($quizzes);
    }
}