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
            if (!Schema::hasColumn('academic_years', 'current_term')) {
                $table->enum('current_term', ['Term1', 'Term2', 'Term3', 'Term4'])->default('Term1')->after('name');
            }
            if (!Schema::hasColumn('academic_years', 'status')) {
                $table->enum('status', ['Current', 'Close'])->default('Current')->after('current_term');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['current_term', 'status']);
        });
    }
};
