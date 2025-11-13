<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_history', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date')->unique();
            $table->integer('total_cadets');
            $table->integer('present_count');
            $table->integer('late_count');
            $table->integer('absent_count');
            $table->timestamps();

            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_history');
    }
};
