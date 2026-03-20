<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_follow_ups')) {
            return;
        }

        Schema::create('attendance_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();
            $table->text('note')->nullable();
            $table->string('status')->default('Not Contacted');
            $table->boolean('resolved')->default(false);
            $table->boolean('is_excused')->default(false);
            $table->timestamps();

            $table->index(['attendance_record_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_follow_ups');
    }
};
