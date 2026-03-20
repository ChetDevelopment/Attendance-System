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
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'academic_year_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->foreign('academic_year_id')
                    ->references('id')
                    ->on('academic_years')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'academic_year_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->foreign('academic_year_id')
                    ->references('id')
                    ->on('academic_years')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'academic_year_id')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'academic_year_id')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropForeign(['academic_year_id']);
            });
        }
    }
};
