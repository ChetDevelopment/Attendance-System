<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('teacher_activities')) {
            return;
        }

        Schema::create('teacher_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('sessions')->nullOnDelete();
            $table->string('action');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['user_id', 'created_at'], 'idx_teacher_activities_user_created_at');
            $table->index(['student_id', 'created_at'], 'idx_teacher_activities_student_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_activities');
    }
};
