<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(SubjectSeeder::class);
        $this->call(SchoolYearSeeder::class);
        $this->call(TermSeeder::class);
        $this->call(ExamSeeder::class);
        $this->call(GradeSeeder::class);
        $this->call(ClassroomSeeder::class);
    }
}
