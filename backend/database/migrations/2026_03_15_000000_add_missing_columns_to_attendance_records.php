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
        Schema::table('attendance_records', function (Blueprint $table) {
            // Add date column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'date')) {
                $table->date('date')->nullable()->after('session_id');
            }

            // Add justification column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'justification')) {
                $table->text('justification')->nullable()->after('location');
            }

            // Add justified_at column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'justified_at')) {
                $table->timestamp('justified_at')->nullable()->after('justification');
            }

            // Add created_by column if it doesn't exist (as alias for submitted_by)
            if (!Schema::hasColumn('attendance_records', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('submitted_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['date', 'justification', 'justified_at', 'created_by']);
        });
    }
};
