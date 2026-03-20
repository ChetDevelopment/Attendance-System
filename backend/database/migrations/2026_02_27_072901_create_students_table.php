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
        if (Schema::hasTable('students')) {
            return;
        }

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('generation');
            $table->string('class');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('profile')->nullable();
            $table->enum('gender', ['Male', 'Female']);
            $table->string('parent_number');
            $table->string('contact');
            $table->timestamps();
            $table->index('generation');
            $table->index('class');
            $table->index('class_id');
            $table->index('academic_year_id');
            $table->index('gender');
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
