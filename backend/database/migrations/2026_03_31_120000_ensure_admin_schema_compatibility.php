<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'generation')) {
                    $table->string('generation')->nullable();
                }

                if (!Schema::hasColumn('students', 'class')) {
                    $table->string('class')->nullable();
                    $table->index('class');
                }
            });

            if (Schema::hasColumn('students', 'class') && Schema::hasColumn('students', 'class_id')) {
                DB::statement("
                    UPDATE students s
                    LEFT JOIN classes c ON c.id = s.class_id
                    SET s.class = COALESCE(s.class, c.class_name, c.name)
                    WHERE s.class IS NULL
                ");
            }
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (!Schema::hasColumn('attendance_records', 'comment')) {
                    $table->text('comment')->nullable();
                }

                // Keep both columns available for compatibility across schemas.
                if (Schema::hasColumn('attendance_records', 'recorded_by') && !Schema::hasColumn('attendance_records', 'submitted_by')) {
                    $table->unsignedBigInteger('submitted_by')->nullable();
                }

                if (Schema::hasColumn('attendance_records', 'submitted_by') && !Schema::hasColumn('attendance_records', 'recorded_by')) {
                    $table->unsignedBigInteger('recorded_by')->nullable();
                }
            });

            if (
                Schema::hasColumn('attendance_records', 'recorded_by') &&
                Schema::hasColumn('attendance_records', 'submitted_by')
            ) {
                DB::statement("
                    UPDATE attendance_records
                    SET submitted_by = COALESCE(submitted_by, recorded_by),
                        recorded_by = COALESCE(recorded_by, submitted_by)
                ");
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank (non-destructive compatibility migration).
    }
};
