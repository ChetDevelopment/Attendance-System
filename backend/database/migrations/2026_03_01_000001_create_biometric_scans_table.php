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
        Schema::create('biometric_scans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->enum('scan_type', ['card', 'fingerprint']);
            $table->string('scan_data')->nullable(); // Card ID or fingerprint reference
            $table->enum('status', ['success', 'failed', 'duplicate', 'invalid']);
            $table->string('failure_reason')->nullable();
            $table->string('device_id')->nullable(); // Hardware device identifier
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('set null');

            // Indexes for performance
            $table->index('student_id');
            $table->index('session_id');
            $table->index('scan_type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['student_id', 'session_id', 'scan_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_scans');
    }
};
