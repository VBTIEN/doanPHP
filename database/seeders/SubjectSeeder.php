<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'subject_code' => 'MATH',
                'subject_name' => 'Toán',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subject_code' => 'LIT',
                'subject_name' => 'Văn',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subject_code' => 'ENG',
                'subject_name' => 'Anh',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subject_code' => 'PHY',
                'subject_name' => 'Lý',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($subjects as $subjectData) {
            Subject::updateOrCreate(
                ['subject_code' => $subjectData['subject_code']],
                $subjectData
            );
        }
    }
}
