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
        // Add attendance_date column as an alias to date
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'attendance_date')) {
                $table->dateTime('attendance_date')->nullable()->after('date');
            }
        });

        // Copy data from date to attendance_date
        DB::statement('UPDATE attendance_records SET attendance_date = date WHERE attendance_date IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['attendance_date']);
        });
    }
};
