<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Try to populate existing rows from users table where possible
        try {
            DB::table('activity_logs')
                ->join('users', 'activity_logs.user_id', '=', 'users.id')
                ->update(['activity_logs.user_name' => DB::raw('users.name')]);
        } catch (\Exception $e) {
            // non-fatal; leave existing user_name null if DB doesn't support join update
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });
    }
};
