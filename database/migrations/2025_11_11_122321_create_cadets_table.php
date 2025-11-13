<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadets', function (Blueprint $table) {
            $table->id();
            $table->string('cadet_id', 50)->unique();
            $table->string('name');
            $table->string('designation');
            $table->string('course_year');
            $table->enum('sex', ['Male', 'Female']);
            $table->timestamps();

            $table->index('cadet_id');
            $table->index('sex');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadets');
    }
};
