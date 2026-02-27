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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_code')->unique(); 
            // Example: PNC2026037

            $table->string('first_name');
            $table->string('last_name');

            $table->string('email')->nullable();

            $table->foreignId('class_id')
                  ->constrained('classes')
                  ->onDelete('cascade');

            $table->string('qr_code')->nullable();
            // For ID card scanning

            $table->string('face_image')->nullable();
            // Store image path for face recognition

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
