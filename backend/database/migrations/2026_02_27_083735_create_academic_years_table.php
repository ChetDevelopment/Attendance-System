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
        if (Schema::hasTable('academic_years')) {
            return;
        }

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('current_term', ['Term1', 'Term2', 'Term3','Term4'])->default('Term1');
            $table->enum('status',['Current', 'Close'])->default('Current');
            $table->timestamps();

            $table->index('status');
            $table->index('current_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
