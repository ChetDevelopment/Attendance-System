<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing indexes to optimize Admin dashboard queries.
     */
    public function up(): void
    {
        // Add index on attendance_records.created_at for date-based queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_created_at')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index('created_at', 'idx_attendance_records_created_at');
            });
        }

        // Add composite index on attendance_records for status and created_at queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_status_created')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_attendance_records_status_created');
            });
        }

        // Add composite index on attendance_records for attendance_date and status queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_date_status')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['attendance_date', 'status'], 'idx_attendance_records_date_status');
            });
        }

        // Add index on absence_notifications.status for status filtering
        if (Schema::hasTable('absence_notifications') && !Schema::hasIndex('absence_notifications', 'idx_absence_notifications_status')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->index('status', 'idx_absence_notifications_status');
            });
        }

        // Add composite index on absence_notifications for status and absence_status
        if (Schema::hasTable('absence_notifications') && !Schema::hasIndex('absence_notifications', 'idx_absence_notifications_status_absence')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->index(['status', 'absence_status'], 'idx_absence_notifications_status_absence');
            });
        }

        // Add index on teacher_activities.created_at for date-based queries
        if (Schema::hasTable('teacher_activities') && !Schema::hasIndex('teacher_activities', 'idx_teacher_activities_created_at')) {
            Schema::table('teacher_activities', function (Blueprint $table) {
                $table->index('created_at', 'idx_teacher_activities_created_at');
            });
        }

        // Add index on students.generation for grouping queries
        if (Schema::hasTable('students') && !Schema::hasIndex('students', 'idx_students_generation')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('generation', 'idx_students_generation');
            });
        }

        // Add index on students.fingerprint_enrolled for filtering queries
        if (Schema::hasTable('students') && !Schema::hasIndex('students', 'idx_students_fingerprint_enrolled')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('fingerprint_enrolled', 'idx_students_fingerprint_enrolled');
            });
        }

        // Add composite index on students for class and generation queries
        if (Schema::hasTable('students') && !Schema::hasIndex('students', 'idx_students_class_generation')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index(['class_id', 'generation'], 'idx_students_class_generation');
            });
        }

        // Add index on academic_years.status for status filtering
        if (Schema::hasTable('academic_years') && !Schema::hasIndex('academic_years', 'idx_academic_years_status')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->index('status', 'idx_academic_years_status');
            });
        }

        // Add composite index on academic_years for status and is_active
        if (Schema::hasTable('academic_years') && !Schema::hasIndex('academic_years', 'idx_academic_years_status_active')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->index(['status', 'is_active'], 'idx_academic_years_status_active');
            });
        }

        // Add index on classes.is_active for filtering queries
        if (Schema::hasTable('classes') && !Schema::hasIndex('classes', 'idx_classes_is_active')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->index('is_active', 'idx_classes_is_active');
            });
        }

        // Add composite index on classes for academic_year and is_active
        if (Schema::hasTable('classes') && !Schema::hasIndex('classes', 'idx_classes_year_active')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->index(['academic_year_id', 'is_active'], 'idx_classes_year_active');
            });
        }

        // Add index on sessions.is_active for filtering queries
        if (Schema::hasTable('sessions') && !Schema::hasIndex('sessions', 'idx_sessions_is_active')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index('is_active', 'idx_sessions_is_active');
            });
        }

        // Add composite index on sessions for time and is_active
        if (Schema::hasTable('sessions') && !Schema::hasIndex('sessions', 'idx_sessions_time_active')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index(['start_time', 'end_time', 'is_active'], 'idx_sessions_time_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        if (Schema::hasTable('sessions') && Schema::hasIndex('sessions', 'idx_sessions_time_active')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('idx_sessions_time_active');
            });
        }

        if (Schema::hasTable('sessions') && Schema::hasIndex('sessions', 'idx_sessions_is_active')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropIndex('idx_sessions_is_active');
            });
        }

        if (Schema::hasTable('classes') && Schema::hasIndex('classes', 'idx_classes_year_active')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex('idx_classes_year_active');
            });
        }

        if (Schema::hasTable('classes') && Schema::hasIndex('classes', 'idx_classes_is_active')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex('idx_classes_is_active');
            });
        }

        if (Schema::hasTable('academic_years') && Schema::hasIndex('academic_years', 'idx_academic_years_status_active')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropIndex('idx_academic_years_status_active');
            });
        }

        if (Schema::hasTable('academic_years') && Schema::hasIndex('academic_years', 'idx_academic_years_status')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->dropIndex('idx_academic_years_status');
            });
        }

        if (Schema::hasTable('students') && Schema::hasIndex('students', 'idx_students_class_generation')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_class_generation');
            });
        }

        if (Schema::hasTable('students') && Schema::hasIndex('students', 'idx_students_fingerprint_enrolled')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_fingerprint_enrolled');
            });
        }

        if (Schema::hasTable('students') && Schema::hasIndex('students', 'idx_students_generation')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_generation');
            });
        }

        if (Schema::hasTable('teacher_activities') && Schema::hasIndex('teacher_activities', 'idx_teacher_activities_created_at')) {
            Schema::table('teacher_activities', function (Blueprint $table) {
                $table->dropIndex('idx_teacher_activities_created_at');
            });
        }

        if (Schema::hasTable('absence_notifications') && Schema::hasIndex('absence_notifications', 'idx_absence_notifications_status_absence')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_absence_notifications_status_absence');
            });
        }

        if (Schema::hasTable('absence_notifications') && Schema::hasIndex('absence_notifications', 'idx_absence_notifications_status')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_absence_notifications_status');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_date_status')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_date_status');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_status_created')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_status_created');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_created_at')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_created_at');
            });
        }
    }
};
