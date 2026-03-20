<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes for dashboard queries
     */
    public function up(): void
    {
        // Add index on attendance_records for status queries (dashboard trends)
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'status') && Schema::hasColumn('attendance_records', 'created_at')) {
                $table->index(['status', 'created_at'], 'idx_attendance_status_created');
            }
            if (Schema::hasColumn('attendance_records', 'student_id') && Schema::hasColumn('attendance_records', 'created_at')) {
                $table->index(['student_id', 'created_at'], 'idx_attendance_student_date');
            }
            if (Schema::hasColumn('attendance_records', 'attendance_date') && Schema::hasColumn('attendance_records', 'status')) {
                $table->index(['attendance_date', 'status'], 'idx_attendance_date_status');
            } elseif (Schema::hasColumn('attendance_records', 'date') && Schema::hasColumn('attendance_records', 'status')) {
                $table->index(['date', 'status'], 'idx_attendance_date_status');
            }
        });

        // Add index on students table for name lookups
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'fullname')) {
                $table->index('fullname', 'idx_students_fullname');
            }
            if (Schema::hasColumn('students', 'class_id') && Schema::hasColumn('students', 'generation')) {
                $table->index(['class_id', 'generation'], 'idx_students_class_gen');
            }
        });

        // Add index on sessions table for active session queries
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'start_time') && Schema::hasColumn('sessions', 'end_time')) {
                $table->index(['start_time', 'end_time'], 'idx_sessions_times');
            }
        });

        // Add index on teacher_activities for recent activity queries
        if (Schema::hasTable('teacher_activities')) {
            Schema::table('teacher_activities', function (Blueprint $table) {
                if (Schema::hasColumn('teacher_activities', 'created_at')) {
                    $table->index(['created_at'], 'idx_teacher_activity_created');
                }
                if (Schema::hasColumn('teacher_activities', 'student_id') && Schema::hasColumn('teacher_activities', 'created_at')) {
                    $table->index(['student_id', 'created_at'], 'idx_teacher_activity_student');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_status_created');
            $table->dropIndex('idx_attendance_student_date');
            $table->dropIndex('idx_attendance_date_status');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_fullname');
            $table->dropIndex('idx_students_class_gen');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_times');
        });

        Schema::table('teacher_activities', function (Blueprint $table) {
            $table->dropIndex('idx_teacher_activity_created');
            $table->dropIndex('idx_teacher_activity_student');
        });
    }
};
