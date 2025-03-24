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
        Schema::table('subject_yearly_averages', function (Blueprint $table) {
            $table->foreign('student_code')->references('student_code')->on('students')->onDelete('cascade');
            $table->foreign('subject_code')->references('subject_code')->on('subjects')->onDelete('cascade');
            $table->foreign('school_year_code')->references('school_year_code')->on('school_years')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_yearly_averages', function (Blueprint $table) {
            $table->dropForeign(['student_code']);
            $table->dropForeign(['subject_code']);
            $table->dropForeign(['school_year_code']);
        });
    }
};
