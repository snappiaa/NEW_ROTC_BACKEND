<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceHistory;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class HistoryController extends Controller
{
    // Get history by month/year (e.g., all weekends)
    public function index(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2100',
        ]);

        $month = $request->month;
        $year  = $request->year;

        // ✅ FIXED: Updated field name to match migration
        $records = AttendanceHistory::whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date', 'desc')
            ->get();

        $weekendDates = $records->map(function($record) {
            $rate = $record->total_cadets > 0
                ? round(($record->present_count + $record->late_count) / $record->total_cadets * 100, 2)
                : 0;

            return [
                'date'           => Carbon::parse($record->attendance_date)->format('Y-m-d'),
                'dayName'        => Carbon::parse($record->attendance_date)->format('l'),
                'present'        => $record->present_count,
                'late'           => $record->late_count,
                'absent'         => $record->absent_count,
                'total'          => $record->total_cadets,
                'attendanceRate' => $rate
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'weekend_dates' => $weekendDates
            ]
        ]);
    }

    // Get detailed attendance for specific date
    public function getDateDetails(Request $request)
    {
        $request->validate([
            'date'   => 'required|date',
            'status' => 'nullable|string|in:present,late,absent',
        ]);

        $date = $request->date;
        $status = $request->status;

        // Get attendance records for the specific date and status
        $query = AttendanceRecord::where('date', $date);

        if ($status) {
            $query->where('status', $status);
        }

        $records = $query->with('cadet')->get();

        $detailedRecords = $records->map(function($record) {
            return [
                'cadetId'     => $record->cadet->cadetid ?? 'N/A',
                'name'        => $record->cadet->name ?? 'N/A',
                'designation' => $record->cadet->designation ?? 'N/A',
                'status'      => $record->status,
                'timestamp'   => $record->check_in_time ?? $record->created_at
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'records' => $detailedRecords
            ]
        ]);
    }

    // Download history as CSV for given date
    public function download(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        $records = AttendanceRecord::where('date', $date)
            ->with('cadet')
            ->orderBy('created_at', 'asc')
            ->get();

        $csv = "Cadet ID,Name,Designation,Status,Timestamp\n";

        foreach ($records as $record) {
            $csv .= sprintf('"%s","%s","%s","%s","%s"' . "\n",
                $record->cadet->cadetid ?? 'N/A',
                $record->cadet->name ?? 'N/A',
                $record->cadet->designation ?? 'N/A',
                $record->status,
                $record->check_in_time ?? $record->created_at
            );
        }

        $filename = 'history-' . $date . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }

    // Save today's attendance to history
    public function saveToHistory(Request $request)
    {
        $validated = $request->validate([
            'date'        => 'required|date',
            'totalcadets' => 'required|integer',
            'present'     => 'required|integer',
            'late'        => 'required|integer',
            'absent'      => 'required|integer',
        ]);

        // ✅ FIXED: Updated field names to match migration
        AttendanceHistory::updateOrCreate(
            ['attendance_date' => $validated['date']],
            [
                'total_cadets'  => $validated['totalcadets'],
                'present_count' => $validated['present'],
                'late_count'    => $validated['late'],
                'absent_count'  => $validated['absent'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'History saved successfully',
        ]);
    }
}
