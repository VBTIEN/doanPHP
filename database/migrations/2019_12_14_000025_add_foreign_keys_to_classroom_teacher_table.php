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
        Schema::table('classroom_teacher', function (Blueprint $table) {
            $table->foreign('classroom_code')->references('classroom_code')->on('classrooms')->onDelete('cascade');
            $table->foreign('teacher_code')->references('teacher_code')->on('teachers')->onDelete('cascade');
            $table->foreign('subject_code')->references('subject_code')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_teacher', function (Blueprint $table) {
            $table->dropForeign(['classroom_code']);
            $table->dropForeign(['teacher_code']);
            $table->dropForeign(['subject_code']);
        });
    }
};
