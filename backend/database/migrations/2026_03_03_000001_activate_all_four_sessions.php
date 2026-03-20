<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Activate all 4 sessions for PNC:
     * - Session 1: 07:30 AM - 09:00 AM (active)
     * - Session 2: 10:00 AM - 11:30 AM (active)
     * - Session 3: 01:00 PM - 02:30 PM (active)
     * - Session 4: 03:30 PM - 05:00 PM (active)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('sessions', 'order') || !Schema::hasColumn('sessions', 'is_active')) {
            return;
        }

        // Activate Session 4
        DB::table('sessions')
            ->where('order', 4)
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('sessions', 'order') || !Schema::hasColumn('sessions', 'is_active')) {
            return;
        }

        // Deactivate Session 4 if needed
        DB::table('sessions')
            ->where('order', 4)
            ->update(['is_active' => false]);
    }
};
