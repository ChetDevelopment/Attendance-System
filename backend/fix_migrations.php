<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Drop the table completely and discard tablespace
    DB::statement('DROP TABLE IF EXISTS migrations');

    // Also try to clean up any orphaned tablespace files
    DB::statement('SET GLOBAL innodb_flush_log_at_trx_commit = 1');

    echo "Migrations table dropped successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
