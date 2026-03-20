<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Synchronizes and standardizes all relevant table schemas for the Admin Dashboard.
     */
    public function up(): void
    {
        // 1. Standardize attendance_records table
        Schema::table('attendance_records', function (Blueprint $table) {
            // Add the canonical column first; data is copied below for MariaDB compatibility.
            if (Schema::hasColumn('attendance_records', 'date') && !Schema::hasColumn('attendance_records', 'attendance_date')) {
                $table->date('attendance_date')->nullable()->after('date');
            }
            
            // Handle recorded_by vs submitted_by vs created_by
            if (Schema::hasColumn('attendance_records', 'recorded_by') && !Schema::hasColumn('attendance_records', 'submitted_by')) {
                $table->renameColumn('recorded_by', 'submitted_by');
            } elseif (Schema::hasColumn('attendance_records', 'created_by') && !Schema::hasColumn('attendance_records', 'submitted_by')) {
                $table->renameColumn('created_by', 'submitted_by');
            }
        });

        if (Schema::hasColumn('attendance_records', 'date') && Schema::hasColumn('attendance_records', 'attendance_date')) {
            DB::table('attendance_records')
                ->whereNull('attendance_date')
                ->update([
                    'attendance_date' => DB::raw('`date`'),
                ]);
        }

        // 2. Standardize students table
        Schema::table('students', function (Blueprint $table) {
            // fullname column is often missing or needed for search
            if (!Schema::hasColumn('students', 'fullname')) {
                $table->string('fullname')->nullable()->after('last_name');
            }
            
            // student_code vs username
            if (!Schema::hasColumn('students', 'username') && Schema::hasColumn('students', 'student_code')) {
                $table->string('username')->nullable()->after('student_code');
            }

            // Ensure academic_year_id exists
            if (!Schema::hasColumn('students', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('class_id')->constrained('academic_years')->nullOnDelete();
            }
        });

        // 3. Ensure users table has all profile/settings columns
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'avatar_url' => 'string',
                'phone' => 'string',
                'bio' => 'text',
                'theme' => 'string',
                'notification_email' => 'boolean',
                'notification_push' => 'boolean',
                'student_id' => 'bigInteger',
            ];

            foreach ($columns as $column => $type) {
                if (!Schema::hasColumn('users', $column)) {
                    if ($type === 'string') $table->string($column)->nullable();
                    elseif ($type === 'text') $table->text($column)->nullable();
                    elseif ($type === 'boolean') $table->boolean($column)->default(true);
                    elseif ($type === 'bigInteger') $table->unsignedBigInteger($column)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Down migration for schema fixes is complex; generally not recommended to auto-revert structural fixes.
    }
};
