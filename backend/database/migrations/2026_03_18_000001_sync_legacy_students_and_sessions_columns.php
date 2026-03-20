<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->syncStudentsTable();
        $this->syncSessionsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'class')) {
                if ($this->hasIndex('students', 'students_class_index')) {
                    $table->dropIndex('students_class_index');
                }
                $table->dropColumn('class');
            }
        });

        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'order')) {
                if ($this->hasIndex('sessions', 'sessions_order_index')) {
                    $table->dropIndex('sessions_order_index');
                }
                $table->dropColumn('order');
            }
        });
    }

    private function syncStudentsTable(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'class')) {
                $positionColumn = Schema::hasColumn('students', 'academic_year_id')
                    ? 'academic_year_id'
                    : (Schema::hasColumn('students', 'class_id') ? 'class_id' : 'fullname');

                $table->string('class')->nullable()->after($positionColumn);
            }
        });

        if (Schema::hasColumn('students', 'class') && Schema::hasColumn('students', 'class_id') && Schema::hasTable('classes')) {
            $classNameColumn = Schema::hasColumn('classes', 'class_name') ? 'class_name' : 'name';

            DB::statement("
                UPDATE students s
                LEFT JOIN classes c ON c.id = s.class_id
                SET s.class = COALESCE(s.class, c.{$classNameColumn})
                WHERE s.class IS NULL OR s.class = ''
            ");
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'class') && !$this->hasIndex('students', 'students_class_index')) {
                $table->index('class');
            }
        });
    }

    private function syncSessionsTable(): void
    {
        if (!Schema::hasTable('sessions')) {
            return;
        }

        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'order')) {
                $table->integer('order')->default(1)->after('end_time');
            }
        });

        if (Schema::hasColumn('sessions', 'order') && Schema::hasColumn('sessions', 'start_time')) {
            $sessions = DB::table('sessions')
                ->select('id')
                ->orderBy('start_time')
                ->orderBy('id')
                ->get();

            foreach ($sessions as $index => $session) {
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['order' => $index + 1]);
            }
        }

        Schema::table('sessions', function (Blueprint $table) {
            if (Schema::hasColumn('sessions', 'order') && !$this->hasIndex('sessions', 'sessions_order_index')) {
                $table->index('order');
            }
        });
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
