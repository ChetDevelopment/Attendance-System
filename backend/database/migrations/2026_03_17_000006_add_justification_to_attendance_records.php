<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds justification columns to attendance_records.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'justification')) {
                $table->text('justification')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendance_records', 'justified_at')) {
                $table->timestamp('justified_at')->nullable()->after('justification');
            }
            if (!Schema::hasColumn('attendance_records', 'justified_by')) {
                $table->foreignId('justified_by')->nullable()->after('justified_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['justified_by']);
            $table->dropColumn(['justification', 'justified_at', 'justified_by']);
        });
    }
};
