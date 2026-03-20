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
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');

                $table->foreignId('role_id')
                    ->constrained('roles')
                    ->onDelete('cascade');

                $table->foreignId('student_id')
                    ->nullable()
                    ->constrained('students')
                    ->onDelete('cascade');

                $table->rememberToken();
                $table->timestamps();

                $table->index('role_id');
                $table->index('student_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
