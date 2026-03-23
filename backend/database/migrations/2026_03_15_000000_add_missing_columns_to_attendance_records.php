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
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            // Add date column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'date')) {
                $table->date('date')->nullable();
            }

            // Add justification column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'justification')) {
                $table->text('justification')->nullable();
            }

            // Add justified_at column if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'justified_at')) {
                $table->timestamp('justified_at')->nullable();
            }

            // Add created_by column if it doesn't exist (as alias for submitted_by)
            if (!Schema::hasColumn('attendance_records', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
        });

        $dropColumns = array_values(array_filter([
            Schema::hasColumn('attendance_records', 'date') ? 'date' : null,
            Schema::hasColumn('attendance_records', 'justification') ? 'justification' : null,
            Schema::hasColumn('attendance_records', 'justified_at') ? 'justified_at' : null,
            Schema::hasColumn('attendance_records', 'created_by') ? 'created_by' : null,
        ]));

        if ($dropColumns !== []) {
            Schema::table('attendance_records', function (Blueprint $table) use ($dropColumns) {
                $table->dropColumn($dropColumns);
            });
        }
    }
};
