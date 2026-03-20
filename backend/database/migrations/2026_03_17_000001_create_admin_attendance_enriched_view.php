<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the admin attendance enriched view for dashboard queries
     */
    public function up(): void
    {
        // First, check if the view already exists and drop it
        DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');

        // Create the enriched view for admin dashboard
        $sql = "
            CREATE VIEW v_admin_attendance_enriched AS
            SELECT 
                ar.id as attendance_id,
                ar.student_id,
                TRIM(CONCAT(COALESCE(s.first_name, ''), ' ', COALESCE(s.last_name, ''))) as student_name,
                c.name as class_name,
                ar.created_at as created_time,
                ar.status,
                ar.date as attendance_date,
                ar.created_at,
                ar.session_id,
                NULL as location
            FROM attendance_records ar
            INNER JOIN students s ON ar.student_id = s.id
            LEFT JOIN sessions sess ON ar.session_id = sess.id
            LEFT JOIN classes c ON s.class_id = c.id
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');
    }
};
