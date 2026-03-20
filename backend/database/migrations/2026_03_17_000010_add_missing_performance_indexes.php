<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes to optimize the Admin Dashboard.
     */
    public function up(): void
    {
        // 1. Optimize attendance_records for reporting and analytics
        Schema::table('attendance_records', function (Blueprint $table) {
            // Index for date-based lookups (very common)
            if (Schema::hasColumn('attendance_records', 'attendance_date') && !$this->hasIndex('attendance_records', 'idx_attendance_date')) {
                $table->index('attendance_date', 'idx_attendance_date');
            }
            
            // Index for student performance lookups
            if (Schema::hasColumn('attendance_records', 'student_id') && Schema::hasColumn('attendance_records', 'attendance_date') && !$this->hasIndex('attendance_records', 'idx_student_date')) {
                $table->index(['student_id', 'attendance_date'], 'idx_student_date');
            }

            // Index for session monitoring
            if (Schema::hasColumn('attendance_records', 'session_id') && Schema::hasColumn('attendance_records', 'attendance_date') && !$this->hasIndex('attendance_records', 'idx_session_date')) {
                $table->index(['session_id', 'attendance_date'], 'idx_session_date');
            }
        });

        // 2. Optimize students for searching and filtering
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'class_id') && !$this->hasIndex('students', 'idx_students_class_id')) {
                $table->index('class_id', 'idx_students_class_id');
            }
            if (Schema::hasColumn('students', 'generation') && !$this->hasIndex('students', 'idx_students_generation')) {
                $table->index('generation', 'idx_students_generation');
            }
        });

        // 3. Optimize sessions
        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'is_active') && !$this->hasIndex('sessions', 'idx_sessions_is_active')) {
                $table->index('is_active', 'idx_sessions_is_active');
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
            $table->dropIndex('idx_attendance_date');
            $table->dropIndex('idx_student_date');
            $table->dropIndex('idx_session_date');
        });
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_students_class_id');
            $table->dropIndex('idx_students_generation');
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_is_active');
        });
    }
};
