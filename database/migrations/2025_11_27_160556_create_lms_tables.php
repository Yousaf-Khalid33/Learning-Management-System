<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. COURSES TABLE
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade'); // Links to a Teacher
            $table->timestamps();
        });

        // 2. SECTIONS TABLE (Chapters within a course)
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->integer('order')->default(0); // To keep chapters in order
            $table->timestamps();
        });

        // 3. MATERIALS TABLE (PDFs, Videos, etc.)
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable(); // Text content or description
            $table->string('file_path')->nullable(); // For uploaded files
            $table->string('type')->default('file'); // 'pdf', 'video', 'text'
            $table->timestamps();
        });

        // 4. QUIZZES TABLE
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->integer('total_questions')->default(0);
            $table->integer('passing_score')->default(50); // e.g., 50% to pass
            $table->timestamps();
        });

        // 5. QUESTIONS TABLE
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->string('type')->default('mcq'); // 'mcq' or 'true_false'
            $table->string('correct_answer')->nullable(); // Stores the correct option ID or text
            $table->timestamps();
        });

        // 6. OPTIONS TABLE (Choices for MCQ)
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // 7. ENROLLMENTS (Which student is in which course)
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();
        });

        // 8. SUBMISSIONS (When a student takes a quiz)
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });

        // 9. SUBMISSION ANSWERS (Detailed record of student answers)
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('student_answer'); // The option ID or text they selected
            $table->timestamps();
        });

        // 10. LEADERBOARD
        Schema::create('leaderboard', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->integer('total_score')->default(0);
            $table->integer('rank')->default(0);
            $table->timestamps();
        });

        // 11. CERTIFICATES
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->date('issue_date');
            $table->string('pdf_path'); // Where the PDF is saved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order to avoid Foreign Key errors
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('leaderboard');
        Schema::dropIfExists('submission_answers');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('courses');
    }
};