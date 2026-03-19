<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'generation')) {
                $table->string('generation')->nullable()->after('email');
            }

            if (!Schema::hasColumn('students', 'profile')) {
                $table->string('profile')->nullable()->after('face_image');
            }

            if (!Schema::hasColumn('students', 'parent_number')) {
                $table->string('parent_number')->nullable()->after('profile');
            }

            if (!Schema::hasColumn('students', 'contact')) {
                $table->string('contact')->nullable()->after('parent_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $dropColumns = [];

            foreach (['generation', 'profile', 'parent_number', 'contact'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
