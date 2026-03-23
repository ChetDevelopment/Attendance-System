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
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        try {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropUnique('attendance_records_student_id_session_id_unique');
            });
        } catch (\Throwable $e) {
            // No-op when the legacy unique index was never created.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unique(['student_id', 'session_id']);
        });
    }
};
