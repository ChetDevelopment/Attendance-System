<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        if (!Schema::hasColumn('attendance_records', 'attendance_id')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->foreignId('attendance_id')
                    ->nullable()
                    ->constrained('attendances')
                    ->onDelete('cascade');
            });
        }

        // Update existing records to link to the correct attendance
        // Based on session_id and attendance_date = date
        if (Schema::hasColumn('attendance_records', 'attendance_date')) {
            DB::statement('
                UPDATE attendance_records ar
                INNER JOIN attendances a ON a.session_id = ar.session_id 
                    AND DATE(a.date) = DATE(ar.attendance_date)
                SET ar.attendance_id = a.id
                WHERE ar.attendance_id IS NULL
            ');
        } elseif (Schema::hasColumn('attendance_records', 'date')) {
            DB::statement('
                UPDATE attendance_records ar
                INNER JOIN attendances a ON a.session_id = ar.session_id 
                    AND DATE(a.date) = DATE(ar.date)
                SET ar.attendance_id = a.id
                WHERE ar.attendance_id IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendance_records') || !Schema::hasColumn('attendance_records', 'attendance_id')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['attendance_id']);
            $table->dropColumn('attendance_id');
        });
    }
};
