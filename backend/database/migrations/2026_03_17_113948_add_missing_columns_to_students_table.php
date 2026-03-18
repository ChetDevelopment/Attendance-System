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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'first_name')) {
                $table->string('first_name')->nullable()->after('fullname');
            }
            if (!Schema::hasColumn('students', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('students', 'student_code')) {
                $table->string('student_code')->nullable()->unique()->after('last_name');
            }
            if (!Schema::hasColumn('students', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('student_code');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'student_code', 'is_active']);
        });
    }
};
