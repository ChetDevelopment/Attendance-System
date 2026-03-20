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
        Schema::table('academic_years', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_years', 'start_year')) {
                $table->integer('start_year')->nullable()->after('name');
            }
            if (!Schema::hasColumn('academic_years', 'end_year')) {
                $table->integer('end_year')->nullable()->after('start_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['start_year', 'end_year']);
        });
    }
};
