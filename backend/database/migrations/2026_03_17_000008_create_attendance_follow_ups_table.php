<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the attendance_follow_ups table used for logging follow-up actions.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendance_follow_ups')) {
            return;
        }

        Schema::create('attendance_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->nullable();
            
            $table->boolean('resolved')->default(false);
            $table->boolean('is_excused')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_follow_ups');
    }
};
