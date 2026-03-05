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
        Schema::create('absence_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')
              ->constrained('attendance_records')
              ->onDelete('cascade');

            $table->text('reason')->nullable();

            $table->enum('excuse_status', ['excused', 'unexcused'])
                ->nullable();

            $table->foreignId('commented_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absence_comments');
    }
};
