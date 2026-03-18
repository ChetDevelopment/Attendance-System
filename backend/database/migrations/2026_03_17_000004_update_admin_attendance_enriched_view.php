<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Updates the admin attendance enriched view to use the real location column.
     */
    public function up(): void
    {
        // Drop the old view
        DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');

        // Recreate the view with correct column selection
        $sql = "
            CREATE VIEW v_admin_attendance_enriched AS
            SELECT 
                ar.id as attendance_id,
                ar.student_id,
                s.fullname as student_name,
                c.class_name as class_name,
                ar.created_at as created_time,
                ar.status,
                ar.attendance_date,
                ar.created_at,
                ar.session_id,
                ar.location
            FROM attendance_records ar
            INNER JOIN students s ON ar.student_id = s.id
            LEFT JOIN classes c ON s.class_id = c.id
            LEFT JOIN sessions sess ON ar.session_id = sess.id
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');
        
        // Restore old broken view definition if needed, but better just drop it.
        // For strict rollback, we'd recreate the old version, but since it was broken, 
        // leaving it dropped or recreating the old broken one is fine.
        // Here we just drop it.
    }
};
