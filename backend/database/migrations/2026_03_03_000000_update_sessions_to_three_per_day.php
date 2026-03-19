<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update sessions to 3 per day:
     * - Session 1: 07:30 AM - 09:00 AM (active)
     * - Session 2: 10:00 AM - 11:30 AM (active)
     * - Session 3: 01:00 PM - 02:30 PM (active)
     * - Session 4: 03:30 PM - 05:00 PM (inactive)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('sessions', 'order') || !Schema::hasColumn('sessions', 'is_active')) {
            return;
        }

        // Deactivate Session 4 (only 3 sessions per day)
        DB::table('sessions')
            ->where('order', 4)
            ->update(['is_active' => false]);
        
        // Ensure only 3 sessions are active
        $activeCount = DB::table('sessions')
            ->where('is_active', true)
            ->count();
            
        if ($activeCount > 3) {
            // If more than 3 sessions are active, deactivate the higher order ones
            DB::table('sessions')
                ->where('is_active', true)
                ->where('order', '>', 3)
                ->update(['is_active' => false]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('sessions', 'order') || !Schema::hasColumn('sessions', 'is_active')) {
            return;
        }

        // Reactivate Session 4
        DB::table('sessions')
            ->where('order', 4)
            ->update(['is_active' => true]);
    }
};
