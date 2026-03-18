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
                s.fullname as student_name,
                c.name as class_name,
                ar.check_in_time as created_time,
                ar.status,
                ar.attendance_date,
                ar.created_at,
                ar.session_id,
                COALESCE(
                    JSON_UNQUOTE(JSON_EXTRACT(ar.check_in_time, '$.location')),
                    NULL
                ) as location
            FROM attendance_records ar
            INNER JOIN students s ON ar.student_id = s.id
            LEFT JOIN sessions sess ON ar.session_id = sess.id
            LEFT JOIN classes c ON c.id = (
                SELECT sc.class_id 
                FROM student_classes sc 
                INNER JOIN student_class_students scs ON scs.student_class_id = sc.id 
                WHERE scs.student_id = s.id 
                LIMIT 1
            )
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
