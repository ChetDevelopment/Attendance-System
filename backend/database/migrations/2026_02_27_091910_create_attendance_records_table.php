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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->onDelete('cascade');

            $table->foreignId('session_id')
                ->constrained('sessions')
                ->onDelete('cascade');

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late']);
            $table->timestamp('check_in_time')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session_id', 'attendance_date'], 'unique_attendance_per_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
