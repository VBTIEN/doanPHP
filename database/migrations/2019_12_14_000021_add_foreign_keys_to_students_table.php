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
        Schema::table('students', function (Blueprint $table) {
            $table->foreign('role_code')->references('role_code')->on('roles')->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('classroom_code')->references('classroom_code')->on('classrooms')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['role_code']);
            $table->dropForeign(['classroom_code']);
        });
    }
};
