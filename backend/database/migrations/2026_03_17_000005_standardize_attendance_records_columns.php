<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardizes column names in attendance_records table.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Rename recorded_by to submitted_by if it exists
            if (Schema::hasColumn('attendance_records', 'recorded_by')) {
                $table->renameColumn('recorded_by', 'submitted_by');
            }

            // Add is_locked if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'submitted_by')) {
                $table->renameColumn('submitted_by', 'recorded_by');
            }
            
            if (Schema::hasColumn('attendance_records', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
