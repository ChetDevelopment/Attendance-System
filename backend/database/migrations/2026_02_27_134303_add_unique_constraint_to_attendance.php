<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        $hasDuplicates = DB::table('attendance_records')
            ->select('student_id', 'session_id')
            ->groupBy('student_id', 'session_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(['student_id', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        try {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropUnique('attendance_records_student_id_session_id_unique');
            });
        } catch (\Throwable $e) {
            // No-op when the legacy index was never created.
        }
    }
};
