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
        Schema::create('student_term_averages', function (Blueprint $table) {
            $table->id();
            $table->string('student_code');
            $table->string('term_code');
            $table->float('term_average')->default(0);
            $table->integer('classroom_rank')->nullable();
            $table->integer('grade_rank')->nullable();
            $table->string('academic_performance')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_term_averages');
    }
};
