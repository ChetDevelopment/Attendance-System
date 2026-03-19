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
        Schema::table('students', function (Blueprint $table) {
            // Card ID for RFID/NFC card scanning
            if (!Schema::hasColumn('students', 'card_id')) {
                $table->string('card_id')->unique()->nullable();
            }
            
            // Fingerprint template data (encrypted/hashed)
            // Store as text for flexibility with different scanner SDKs
            if (!Schema::hasColumn('students', 'fingerprint_template')) {
                $table->text('fingerprint_template')->nullable();
            }
            
            // Fingerprint enrollment status
            if (!Schema::hasColumn('students', 'fingerprint_enrolled')) {
                $table->boolean('fingerprint_enrolled')->default(false);
            }
            
            // Last biometric scan timestamp
            if (!Schema::hasColumn('students', 'last_biometric_scan')) {
                $table->timestamp('last_biometric_scan')->nullable();
            }
            
            // Indexes for faster lookups
            if (Schema::hasColumn('students', 'card_id')) {
                $table->index('card_id');
            }
            if (Schema::hasColumn('students', 'fingerprint_enrolled')) {
                $table->index('fingerprint_enrolled');
            }
            if (Schema::hasColumn('students', 'last_biometric_scan')) {
                $table->index('last_biometric_scan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'card_id',
                'fingerprint_template',
                'fingerprint_enrolled',
                'last_biometric_scan',
            ]);
        });
    }
};
