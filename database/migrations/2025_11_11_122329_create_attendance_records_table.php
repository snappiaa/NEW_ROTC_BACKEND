<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('cadet_id', 50);
            $table->enum('status', ['present', 'late', 'absent']);
            $table->timestamp('timestamp');
            $table->date('attendance_date');
            $table->time('attendance_time');
            $table->timestamps();

            $table->foreign('cadet_id')
                  ->references('cadet_id')
                  ->on('cadets')
                  ->onDelete('cascade');

            $table->index('cadet_id');
            $table->index('status');
            $table->index('attendance_date');
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
