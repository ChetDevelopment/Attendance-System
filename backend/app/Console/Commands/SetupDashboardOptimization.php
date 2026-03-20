<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupDashboardOptimization extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:setup {--force : Force setup even if already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup database view and indexes for dashboard optimization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up dashboard optimization...');

        // Create the view
        $this->createView();
        
        // Create indexes
        $this->createIndexes();
        
        $this->info('Dashboard optimization setup completed!');
        
        return Command::SUCCESS;
    }

    /**
     * Create the admin attendance enriched view
     */
    protected function createView()
    {
        $this->info('Creating v_admin_attendance_enriched view...');
        
        try {
            // Drop view if exists and force flag is used
            if ($this->option('force')) {
                DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');
            }

            // Check if view already exists
            $exists = DB::select("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = 'v_admin_attendance_enriched'");
            
            if (!empty($exists) && !$this->option('force')) {
                $this->warn('View already exists. Use --force to recreate.');
                return;
            }

            // Create the view - matching actual database schema
            // Students table has: student_code, first_name, last_name, email, class_id
            // Classes table has: name, code
            // Attendance records has: student_id, session_id, status, check_in_time
            $sql = "
                CREATE VIEW v_admin_attendance_enriched AS
                SELECT 
                    ar.id as attendance_id,
                    ar.id,
                    ar.student_id,
                    s.fullname as student_name,
                    c.class_name as class_name,
                    ar.created_at as created_time,
                    ar.status,
                    ar.created_at,
                    ar.session_id,
                    ar.location
                FROM attendance_records ar
                INNER JOIN students s ON ar.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                LEFT JOIN sessions sess ON ar.session_id = sess.id
            ";

            DB::statement('DROP VIEW IF EXISTS v_admin_attendance_enriched');
            DB::statement($sql);
            
            $this->info('View created successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to create view: ' . $e->getMessage());
        }
    }

    /**
     * Create performance indexes
     */
    protected function createIndexes()
    {
        $this->info('Creating performance indexes...');
        
        try {
            // Indexes for attendance_records
            $indexes = [
                'attendance_records' => [
                    ['columns' => ['status', 'created_at'], 'name' => 'idx_attendance_status_created'],
                    ['columns' => ['student_id', 'created_at'], 'name' => 'idx_attendance_student_date'],
                    ['columns' => ['attendance_date', 'status'], 'name' => 'idx_attendance_date_status'],
                ],
                'students' => [
                    ['columns' => ['fullname'], 'name' => 'idx_students_fullname'],
                    ['columns' => ['class_id', 'generation'], 'name' => 'idx_students_class_gen'],
                ],
                'sessions' => [
                    ['columns' => ['start_time', 'end_time'], 'name' => 'idx_sessions_times'],
                ],
            ];

            // Check if teacher_activities table exists
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(function($table) {
                return array_values((array)$table)[0];
            }, $tables);

            if (in_array('teacher_activities', $tableNames)) {
                $indexes['teacher_activities'] = [
                    ['columns' => ['created_at'], 'name' => 'idx_teacher_activity_created'],
                    ['columns' => ['student_id', 'created_at'], 'name' => 'idx_teacher_activity_student'],
                ];
            }

            foreach ($indexes as $table => $tableIndexes) {
                if (!in_array($table, $tableNames)) {
                    $this->warn("Table {$table} does not exist, skipping indexes.");
                    continue;
                }

                foreach ($tableIndexes as $index) {
                    try {
                        $columns = implode(', ', $index['columns']);
                        DB::statement("CREATE INDEX IF NOT EXISTS {$index['name']} ON {$table} ({$columns})");
                        $this->info("Created index {$index['name']} on {$table}");
                    } catch (\Exception $e) {
                        // Index might already exist
                        $this->warn("Index {$index['name']} may already exist: " . $e->getMessage());
                    }
                }
            }

            $this->info('Indexes created successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to create indexes: ' . $e->getMessage());
        }
    }
}
