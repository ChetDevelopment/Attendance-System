<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardizes column names in attendance_records table.
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            // On legacy schemas we can have recorded_by instead of submitted_by.
            // Add the new column and copy values instead of renaming so this works
            // on MariaDB versions that choke on the generated rename syntax.
            if (
                Schema::hasColumn('attendance_records', 'recorded_by') &&
                !Schema::hasColumn('attendance_records', 'submitted_by')
            ) {
                $table->unsignedBigInteger('submitted_by')->nullable();
            }

            // Add is_locked if it doesn't exist
            if (!Schema::hasColumn('attendance_records', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('status');
            }
        });

        if (
            Schema::hasColumn('attendance_records', 'recorded_by') &&
            Schema::hasColumn('attendance_records', 'submitted_by')
        ) {
            DB::table('attendance_records')->update([
                'submitted_by' => DB::raw('COALESCE(submitted_by, recorded_by)'),
            ]);
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

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
