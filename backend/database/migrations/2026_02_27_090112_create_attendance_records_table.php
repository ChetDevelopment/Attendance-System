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

            // Link to student
            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');

            // Link to session
            $table->foreignId('session_id')
                  ->constrained('sessions')
                  ->onDelete('cascade');

            $table->enum('status', ['Present', 'Absent', 'Late', 'Excused'])->default('Present');
            $table->string('location')->nullable(); // e.g., "Classroom 101"
            
            // Link to user/teacher who submitted the record
            $table->foreignId('submitted_by')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->timestamps();

            $table->index('student_id');
            $table->index('session_id');
            $table->index('submitted_by');
            $table->index('status');
            $table->index(['student_id', 'session_id']);
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
