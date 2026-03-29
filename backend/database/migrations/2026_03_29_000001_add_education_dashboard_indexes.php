<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite indexes to optimize Education Dashboard queries.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Composite index for riskStudents query: (student_id, status, attendance_date)
            if (!$this->hasIndex('attendance_records', 'idx_student_status_date')) {
                $table->index(['student_id', 'status', 'attendance_date'], 'idx_student_status_date');
            }

            // Composite index for classReports query: (attendance_date, status)
            if (!$this->hasIndex('attendance_records', 'idx_date_status')) {
                $table->index(['attendance_date', 'status'], 'idx_date_status');
            }

            // Index for status queries (if not already exists)
            if (!$this->hasIndex('attendance_records', 'idx_status')) {
                $table->index('status', 'idx_status');
            }
        });

        Schema::table('absence_notifications', function (Blueprint $table) {
            // Composite index for absentToday and allAbsent queries
            if (!$this->hasIndex('absence_notifications', 'idx_status_absence_status')) {
                $table->index(['status', 'absence_status'], 'idx_status_absence_status');
            }

            // Index for attendance_record_id lookups
            if (!$this->hasIndex('absence_notifications', 'idx_attendance_record_id')) {
                $table->index('attendance_record_id', 'idx_attendance_record_id');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            // Index for class_id lookups (if not already exists)
            if (!$this->hasIndex('students', 'idx_class_id')) {
                $table->index('class_id', 'idx_class_id');
            }
        });

        Schema::table('attendance_follow_ups', function (Blueprint $table) {
            // Index for attendance_record_id lookups
            if (!$this->hasIndex('attendance_follow_ups', 'idx_attendance_record_id')) {
                $table->index('attendance_record_id', 'idx_attendance_record_id');
            }
        });
    }

    /**
     * Helper to check if index exists.
     */
    private function hasIndex($table, $name): bool
    {
        $conn = Schema::getConnection();
        $db = $conn->getDatabaseName();

        $results = $conn->select("
            SELECT COUNT(*) as count 
            FROM information_schema.statistics 
            WHERE table_schema = ? 
            AND table_name = ? 
            AND index_name = ?
        ", [$db, $table, $name]);

        return $results[0]->count > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('idx_student_status_date');
            $table->dropIndex('idx_date_status');
            $table->dropIndex('idx_status');
        });

        Schema::table('absence_notifications', function (Blueprint $table) {
            $table->dropIndex('idx_status_absence_status');
            $table->dropIndex('idx_attendance_record_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_class_id');
        });

        Schema::table('attendance_follow_ups', function (Blueprint $table) {
            $table->dropIndex('idx_attendance_record_id');
        });
    }
};
