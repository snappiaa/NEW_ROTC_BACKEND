<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cadet;

class CadetSeeder extends Seeder
{
    public function run(): void
    {
        $designations = ['Alpha Company', 'Bravo Company', 'Charlie Company', 'Delta Company'];
        $courses = ['1st Year BSIT', '2nd Year BSCS', '3rd Year BSBA', '4th Year BSIT'];

        for ($i = 1; $i <= 300; $i++) {
            $cadetId = '231-' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $sex = ($i % 2 === 0) ? 'Male' : 'Female';

            Cadet::create([
                'cadet_id' => $cadetId,
                'name' => $cadetId,
                'designation' => $designations[array_rand($designations)],
                'course_year' => $courses[array_rand($courses)],
                'sex' => $sex,
            ]);
        }
    }
}
