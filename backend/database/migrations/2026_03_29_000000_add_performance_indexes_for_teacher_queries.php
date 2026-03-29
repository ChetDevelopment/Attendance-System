<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing indexes to optimize Teacher dashboard queries.
     */
    public function up(): void
    {
        // Add index on attendances.submitted_by for getHistory() query
        if (Schema::hasTable('attendances') && !Schema::hasIndex('attendances', 'idx_attendances_submitted_by')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index('submitted_by', 'idx_attendances_submitted_by');
            });
        }

        // Add composite index on attendances for date-based queries
        if (Schema::hasTable('attendances') && !Schema::hasIndex('attendances', 'idx_attendances_date_submitted')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['date', 'submitted_by'], 'idx_attendances_date_submitted');
            });
        }

        // Add index on attendance_records.attendance_date for date-based queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_attendance_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index('attendance_date', 'idx_attendance_records_attendance_date');
            });
        }

        // Add composite index on attendance_records for status and date queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_status_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['status', 'attendance_date'], 'idx_attendance_records_status_date');
            });
        }

        // Add composite index on attendance_records for submitted_by and date queries
        if (Schema::hasTable('attendance_records') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_submitted_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['submitted_by', 'attendance_date'], 'idx_attendance_records_submitted_date');
            });
        }

        // Add index on attendance_records.attendance_id for JOIN operations
        if (Schema::hasTable('attendance_records') && Schema::hasColumn('attendance_records', 'attendance_id') && !Schema::hasIndex('attendance_records', 'idx_attendance_records_attendance_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index('attendance_id', 'idx_attendance_records_attendance_id');
            });
        }

        // Add index on students.class_id for class-based queries
        if (Schema::hasTable('students') && !Schema::hasIndex('students', 'idx_students_class_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->index('class_id', 'idx_students_class_id');
            });
        }

        // Add composite index on absence_notifications for attendance_record_id and status
        if (Schema::hasTable('absence_notifications') && !Schema::hasIndex('absence_notifications', 'idx_absence_notifications_record_status')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->index(['attendance_record_id', 'status'], 'idx_absence_notifications_record_status');
            });
        }

        // Add index on classes.teacher_id for teacher-based queries
        if (Schema::hasTable('classes') && Schema::hasColumn('classes', 'teacher_id') && !Schema::hasIndex('classes', 'idx_classes_teacher_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->index('teacher_id', 'idx_classes_teacher_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        if (Schema::hasTable('classes') && Schema::hasIndex('classes', 'idx_classes_teacher_id')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex('idx_classes_teacher_id');
            });
        }

        if (Schema::hasTable('absence_notifications') && Schema::hasIndex('absence_notifications', 'idx_absence_notifications_record_status')) {
            Schema::table('absence_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_absence_notifications_record_status');
            });
        }

        if (Schema::hasTable('students') && Schema::hasIndex('students', 'idx_students_class_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropIndex('idx_students_class_id');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_attendance_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_attendance_id');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_submitted_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_submitted_date');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_status_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_status_date');
            });
        }

        if (Schema::hasTable('attendance_records') && Schema::hasIndex('attendance_records', 'idx_attendance_records_attendance_date')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_records_attendance_date');
            });
        }

        if (Schema::hasTable('attendances') && Schema::hasIndex('attendances', 'idx_attendances_date_submitted')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendances_date_submitted');
            });
        }

        if (Schema::hasTable('attendances') && Schema::hasIndex('attendances', 'idx_attendances_submitted_by')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_attendances_submitted_by');
            });
        }
    }
};
