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
        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreign('homeroom_teacher_code')->references('teacher_code')->on('teachers')->onDelete('set null');
            $table->foreign('grade_code')->references('grade_code')->on('grades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropForeign(['homeroom_teacher_code']);
            $table->dropForeign(['grade_code']);
        });
    }
};
