<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceHistory;
use App\Models\AttendanceRecord;
use App\Models\Cadet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class HistoryController extends Controller
{
    /**
     * Get historical attendance data for specific month
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $monthName = Carbon::createFromDate($request->year, $request->month, 1)->format('F Y');

        // Get weekend dates for the month from attendance_history
        $weekendDates = AttendanceHistory::whereYear('attendance_date', $request->year)
            ->whereMonth('attendance_date', $request->month)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $weekendData = $weekendDates->map(function ($record) {
            $date = Carbon::parse($record->attendance_date);
            $attendanceRate = $record->total_cadets > 0
                ? round((($record->present_count + $record->late_count) / $record->total_cadets) * 100, 2)
                : 0;

            return [
                'date' => $record->attendance_date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'present' => $record->present_count,
                'late' => $record->late_count,
                'absent' => $record->absent_count,
                'total' => $record->total_cadets,
                'attendance_rate' => $attendanceRate,
            ];
        });

        // Prepare graph data
        $graphDates = [];
        $graphPresent = [];
        $graphLate = [];
        $graphAbsent = [];

        foreach ($weekendDates->sortBy('attendance_date') as $record) {
            $date = Carbon::parse($record->attendance_date);
            $graphDates[] = $date->format('M d');
            $graphPresent[] = $record->present_count;
            $graphLate[] = $record->late_count;
            $graphAbsent[] = $record->absent_count;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $monthName,
                'weekend_dates' => $weekendData,
                'graph_data' => [
                    'dates' => $graphDates,
                    'present' => $graphPresent,
                    'late' => $graphLate,
                    'absent' => $graphAbsent,
                ],
            ],
        ], 200);
    }

    /**
     * Get detailed attendance for specific date
     */
    public function getDateDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'status' => 'required|in:present,late,absent',
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

            $records = $cadets->map(function ($cadet) {
                return [
                    'cadet_id' => $cadet->cadet_id,
                    'name' => $cadet->name,
                    'designation' => $cadet->designation,
                    'timestamp' => null,
                ];
            });
        } else {
            $attendanceRecords = AttendanceRecord::with('cadet')
                ->where('attendance_date', $request->date)
                ->where('status', $request->status)
                ->orderBy('timestamp', 'asc')
                ->paginate($perPage);

            $records = $attendanceRecords->map(function ($record) {
                return [
                    'cadet_id' => $record->cadet->cadet_id,
                    'name' => $record->cadet->name,
                    'designation' => $record->cadet->designation,
                    'timestamp' => $record->timestamp,
                ];
            });

            $cadets = $attendanceRecords;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $request->date,
                'status' => $request->status,
                'records' => $records,
                'pagination' => [
                    'current_page' => $cadets->currentPage(),
                    'per_page' => $cadets->perPage(),
                    'total' => $cadets->total(),
                    'last_page' => $cadets->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Download history for specific date
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $records = AttendanceRecord::with('cadet')
            ->where('attendance_date', $request->date)
            ->orderBy('timestamp', 'asc')
            ->get();

        $csv = "Name,Cadet ID,Status,Time\n";

        foreach ($records as $record) {
            $time = Carbon::parse($record->timestamp)->format('h:i A');
            $csv .= sprintf(
                "%s,%s,%s,%s\n",
                $record->cadet->name,
                $record->cadet->cadet_id,
                ucfirst($record->status),
                $time
            );
        }

        $filename = 'history-' . $request->date . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
