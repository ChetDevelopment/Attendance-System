<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the absence_notifications table used for student follow-ups.
     */
    public function up(): void
    {
        Schema::create('absence_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('sessions')->onDelete('cascade');
            $table->foreignId('attendance_record_id')->nullable()->constrained('attendance_records')->onDelete('set null');
            
            $table->string('absence_reason')->nullable();
            $table->enum('absence_status', ['PENDING', 'EXCUSED', 'UNEXCUSED'])->default('PENDING');
            $table->text('comment')->nullable();
            $table->text('follow_up_notes')->nullable();
            
            $table->foreignId('reason_submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reason_submitted_at')->nullable();
            
            $table->foreignId('status_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('status_updated_at')->nullable();
            
            $table->string('status')->default('active'); // General status of the notification itself
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_notifications');
    }
};
