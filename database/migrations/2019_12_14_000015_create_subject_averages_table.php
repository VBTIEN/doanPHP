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
        Schema::create('subject_averages', function (Blueprint $table) {
            $table->id();
            $table->string('student_code');
            $table->string('subject_code');
            $table->string('term_code');
            $table->decimal('term_average', 4, 2)->nullable(); // Điểm trung bình trong kỳ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_averages');
    }
};
