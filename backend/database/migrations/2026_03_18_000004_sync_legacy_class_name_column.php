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
        if (!Schema::hasTable('classes')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            if (!Schema::hasColumn('classes', 'class_name')) {
                $table->string('class_name')->nullable()->after('name');
            }
        });

        if (Schema::hasColumn('classes', 'name') && Schema::hasColumn('classes', 'class_name')) {
            DB::table('classes')
                ->where(function ($query) {
                    $query->whereNull('class_name')
                        ->orWhere('class_name', '');
                })
                ->update([
                    'class_name' => DB::raw('name'),
                ]);
        }

        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'class_name') && !$this->hasIndex('classes', 'classes_class_name_index')) {
                $table->index('class_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('classes')) {
            return;
        }

        Schema::table('classes', function (Blueprint $table) {
            if (Schema::hasColumn('classes', 'class_name')) {
                if ($this->hasIndex('classes', 'classes_class_name_index')) {
                    $table->dropIndex('classes_class_name_index');
                }

                $table->dropColumn('class_name');
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
