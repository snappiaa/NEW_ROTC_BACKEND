<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Cadet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get attendance report for date range
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalCadets = Cadet::count();

        // Get all records in date range
        $records = AttendanceRecord::whereBetween('attendance_date', [$startDate, $endDate])->get();

        // Calculate overall statistics
        $totalPresent = $records->where('status', 'present')->count();
        $totalLate = $records->where('status', 'late')->count();
        $totalAbsent = ($totalCadets * $startDate->diffInDays($endDate) + 1) - $records->count();

        // Calculate daily data
        $dailyData = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayRecords = $records->where('attendance_date', $dateStr);

            $present = $dayRecords->where('status', 'present')->count();
            $late = $dayRecords->where('status', 'late')->count();
            $absent = $totalCadets - $dayRecords->count();
            $attendanceRate = $totalCadets > 0 ? round(($dayRecords->count() / $totalCadets) * 100, 2) : 0;

            $dailyData[] = [
                'date' => $dateStr,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'attendance_rate' => $attendanceRate,
            ];

            $currentDate->addDay();
        }

        $averageAttendanceRate = count($dailyData) > 0
            ? round(array_sum(array_column($dailyData, 'attendance_rate')) / count($dailyData), 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'total_cadets' => $totalCadets,
                'overall_statistics' => [
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_absent' => $totalAbsent,
                    'average_attendance_rate' => $averageAttendanceRate,
                ],
                'daily_data' => $dailyData,
            ],
        ], 200);
    }

    /**
     * Get students by status for specific date
     */
    public function studentsByStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent,all',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage = $request->input('per_page', 30);

        if ($request->status === 'absent') {
            // Get cadets who didn't check in
            $presentCadetIds = AttendanceRecord::where('attendance_date', $request->date)
                ->pluck('cadet_id');

            $cadets = Cadet::whereNotIn('cadet_id', $presentCadetIds)
                ->orderBy('cadet_id', 'asc')
                ->paginate($perPage);

            $students = $cadets->map(function ($cadet) {
                return [
                    'cadet_id' => $cadet->cadet_id,
                    'name' => $cadet->name,
                    'designation' => $cadet->designation,
                    'course_year' => $cadet->course_year,
                    'timestamp' => null,
                    'status' => 'absent',
                ];
            });
        } else {
            // Get cadets with specific status
            $query = AttendanceRecord::with('cadet')
                ->where('attendance_date', $request->date);

            if ($request->status !== 'all') {
                $query->where('status', $request->status);
            }

            $records = $query->orderBy('timestamp', 'asc')->paginate($perPage);

            $students = $records->map(function ($record) {
                return [
                    'cadet_id' => $record->cadet->cadet_id,
                    'name' => $record->cadet->name,
                    'designation' => $record->cadet->designation,
                    'course_year' => $record->cadet->course_year,
                    'timestamp' => $record->timestamp,
                    'status' => $record->status,
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $request->date,
                'status' => $request->status,
                'students' => $students,
                'pagination' => [
                    'current_page' => $cadets->currentPage() ?? $records->currentPage(),
                    'per_page' => $cadets->perPage() ?? $records->perPage(),
                    'total' => $cadets->total() ?? $records->total(),
                    'last_page' => $cadets->lastPage() ?? $records->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Download report as CSV
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $totalCadets = Cadet::count();

        $records = AttendanceRecord::whereBetween('attendance_date', [$startDate, $endDate])->get();

        $csv = "Date,Total Cadets,Present,Late,Absent,Attendance Rate\n";

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $dayRecords = $records->where('attendance_date', $dateStr);

            $present = $dayRecords->where('status', 'present')->count();
            $late = $dayRecords->where('status', 'late')->count();
            $absent = $totalCadets - $dayRecords->count();
            $attendanceRate = $totalCadets > 0
                ? round(($dayRecords->count() / $totalCadets) * 100, 2)
                : 0;

            $csv .= sprintf(
                "%s,%d,%d,%d,%d,%s%%\n",
                $dateStr,
                $totalCadets,
                $present,
                $late,
                $absent,
                $attendanceRate
            );

            $currentDate->addDay();
        }

        $filename = 'report-' . $request->start_date . '-to-' . $request->end_date . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
