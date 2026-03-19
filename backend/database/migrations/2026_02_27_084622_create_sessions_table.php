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
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->string('name'); // e.g., "Session 1", "Session 2"
            $table->time('start_time'); // e.g., "08:00:00"
            $table->time('end_time');   // e.g., "10:00:00"
            $table->integer('order')->default(1); // order of the session in the day
            $table->timestamps();

            $table->index('academic_year_id');
            $table->index('order');
            $table->unique(['academic_year_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
