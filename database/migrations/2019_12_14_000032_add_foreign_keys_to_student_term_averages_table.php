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
        Schema::table('student_term_averages', function (Blueprint $table) {
            $table->foreign('student_code')->references('student_code')->on('students')->onDelete('cascade');
            $table->foreign('term_code')->references('term_code')->on('terms')->onDelete('cascade');
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_term_averages', function (Blueprint $table) {
            $table->dropForeign(['student_code']);
            $table->dropForeign(['term_code']);
        });
    }
};
