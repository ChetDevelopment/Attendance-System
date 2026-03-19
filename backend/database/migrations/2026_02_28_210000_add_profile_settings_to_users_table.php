<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'avatar_url')) {
                $table->string('avatar_url')->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('avatar_url');
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'theme')) {
                $table->string('theme')->default('light')->after('bio');
            }
            if (! Schema::hasColumn('users', 'notification_email')) {
                $table->boolean('notification_email')->default(true)->after('theme');
            }
            if (! Schema::hasColumn('users', 'notification_push')) {
                $table->boolean('notification_push')->default(true)->after('notification_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $drop = [];
            foreach (['avatar_url', 'phone', 'bio', 'theme', 'notification_email', 'notification_push'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $drop[] = $column;
                }
            }
            if (! empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};
