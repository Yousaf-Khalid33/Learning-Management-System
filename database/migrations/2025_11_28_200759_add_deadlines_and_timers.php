<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add Due Date to Assignments (Materials)
        Schema::table('materials', function (Blueprint $table) {
            $table->timestamp('due_date')->nullable()->after('type');
        });

        // 2. Add 'Late' Flag to Assignment Submissions
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('file_path');
        });

        // 3. Add Time Limit to Quizzes (in Minutes)
        Schema::table('quizzes', function (Blueprint $table) {
            $table->integer('time_limit')->nullable()->comment('In minutes')->after('passing_score');
        });

        // 4. Add Start Time to Quiz Submissions (To calculate expiry)
        Schema::table('submissions', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) { $table->dropColumn('due_date'); });
        Schema::table('assignment_submissions', function (Blueprint $table) { $table->dropColumn('is_late'); });
        Schema::table('quizzes', function (Blueprint $table) { $table->dropColumn('time_limit'); });
        Schema::table('submissions', function (Blueprint $table) { $table->dropColumn('started_at'); });
    }
};