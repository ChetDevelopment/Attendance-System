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
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'late_threshold')) {
                $table->integer('late_threshold')->default(15); // minutes after start_time considered late
            }
            if (!Schema::hasColumn('sessions', 'is_active')) {
                $table->boolean('is_active')->default(true); // enable/disable session
            }
            if (!Schema::hasColumn('sessions', 'description')) {
                $table->string('description')->nullable(); // optional description
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn(['late_threshold', 'is_active', 'description']);
        });
    }
};
