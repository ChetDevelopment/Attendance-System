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
        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'justification_status')) {
                $table->enum('justification_status', ['pending', 'approved', 'rejected'])->nullable()->after('recorded_at');
            }
            if (!Schema::hasColumn('attendance_records', 'comment')) {
                $table->text('comment')->nullable()->after('justification_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['justification_status', 'comment']);
        });
    }
};
