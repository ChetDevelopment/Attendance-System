<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        if (!Schema::hasColumn('attendance_records', 'recorded_at')) {
            $afterColumn = Schema::hasColumn('attendance_records', 'check_in_time')
                ? 'check_in_time'
                : null;

            Schema::table('attendance_records', function (Blueprint $table) use ($afterColumn) {
                $column = $table->timestamp('recorded_at')->nullable();

                if ($afterColumn !== null) {
                    $column->after($afterColumn);
                }
            });
        }

        // Alter enum values to uppercase - use raw statement to be safe across drivers
        // MySQL / MariaDB
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('PRESENT','ABSENT','LATE') NOT NULL");
        } else {
            // For other DBs, attempt to update existing values and keep column as string
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->string('status', 20)->change();
            });
            DB::table('attendance_records')->update(['status' => DB::raw("UPPER(status)")]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        // Revert enum values to lowercase and drop recorded_at
        if (config('database.default') === 'mysql') {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present','absent','late') NOT NULL");
        } else {
            DB::table('attendance_records')->update(['status' => DB::raw("LOWER(status)")]);
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->enum('status', ['present', 'absent', 'late'])->change();
            });
        }

        if (Schema::hasColumn('attendance_records', 'recorded_at')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropColumn('recorded_at');
            });
        }
    }
};
