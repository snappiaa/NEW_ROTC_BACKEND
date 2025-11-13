<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cadet;
use App\Models\AttendanceRecord;
use App\Models\AttendanceHistory;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $cadets = Cadet::all();

        // September 2024 weekend dates
        $dates = [
            '2024-09-06',
            '2024-09-07',
            '2024-09-13',
            '2024-09-14',
            '2024-09-27',
            '2024-09-28',
        ];

        foreach ($dates as $date) {
            $presentCount = 0;
            $lateCount = 0;
            $absentCount = 0;

            foreach ($cadets as $cadet) {
                // 75% present, 25% late, rest absent
                $rand = rand(1, 100);

                if ($rand <= 60) {
                    $status = 'present';
                    $time = '07:' . rand(20, 59) . ':00';
                    $presentCount++;
                } elseif ($rand <= 80) {
                    $status = 'late';
                    $time = '08:' . rand(31, 59) . ':00';
                    $lateCount++;
                } else {
                    continue; // Absent - no record
                }

                AttendanceRecord::create([
                    'cadet_id' => $cadet->cadet_id,
                    'status' => $status,
                    'timestamp' => Carbon::parse($date . ' ' . $time),
                    'attendance_date' => $date,
                    'attendance_time' => $time,
                ]);
            }

            $absentCount = 300 - $presentCount - $lateCount;

            AttendanceHistory::create([
                'attendance_date' => $date,
                'total_cadets' => 300,
                'present_count' => $presentCount,
                'late_count' => $lateCount,
                'absent_count' => $absentCount,
            ]);
        }
    }
}
