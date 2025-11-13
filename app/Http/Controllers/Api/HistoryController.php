<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceHistory;
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
        $records = AttendanceHistory::whereMonth('attendancedate', $month)
            ->whereYear('attendancedate', $year)
            ->orderBy('attendancedate', 'desc')
            ->get();
        $data = $records->map(function($record) {
            $rate = $record->totalcadets > 0
                ? round(($record->presentcount + $record->latecount) / $record->totalcadets * 100, 2)
                : 0;
            return [
                'date'          => Carbon::parse($record->attendancedate)->format('Y-m-d'),
                'dayname'       => Carbon::parse($record->attendancedate)->format('l'),
                'present'       => $record->presentcount,
                'late'          => $record->latecount,
                'absent'        => $record->absentcount,
                'total'         => $record->totalcadets,
                'attendancerate'=> $rate
            ];
        });
        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }

    // Get detailed attendance for specific date
    public function getDateDetails(Request $request)
    {
        $request->validate([
            'date'   => 'required|date',
        ]);
        $record = AttendanceHistory::where('attendancedate', $request->date)->first();
        if (!$record) {
            return response()->json([
                'success' => false,
                'message' => 'No record for this date.'
            ], 404);
        }
        $rate = $record->totalcadets > 0
            ? round(($record->presentcount + $record->latecount) / $record->totalcadets * 100, 2)
            : 0;
        return response()->json([
            'success' => true,
            'data'    => [
                'date'          => $record->attendancedate,
                'present'       => $record->presentcount,
                'late'          => $record->latecount,
                'absent'        => $record->absentcount,
                'total'         => $record->totalcadets,
                'attendancerate'=> $rate
            ]
        ]);
    }

    // Download history as CSV for given month/year
    public function download(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2100',
        ]);
        $month = $request->month;
        $year  = $request->year;
        $records = AttendanceHistory::whereMonth('attendancedate', $month)
            ->whereYear('attendancedate', $year)
            ->orderBy('attendancedate', 'asc')
            ->get();
        $csv = "Date,Present,Late,Absent,Total,Attendance Rate\n";
        foreach ($records as $record) {
            $rate = $record->totalcadets > 0
                ? round(($record->presentcount + $record->latecount) / $record->totalcadets * 100, 2)
                : 0;
            $csv .= sprintf('%s,%d,%d,%d,%d,%.2f\n',
                $record->attendancedate,
                $record->presentcount,
                $record->latecount,
                $record->absentcount,
                $record->totalcadets,
                $rate
            );
        }
        $filename = 'history-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
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
        AttendanceHistory::updateOrCreate(
            ['attendancedate' => $validated['date']],
            [
                'totalcadets'  => $validated['totalcadets'],
                'presentcount' => $validated['present'],
                'latecount'    => $validated['late'],
                'absentcount'  => $validated['absent'],
            ]
        );
        return response()->json([
            'success' => true,
            'message' => 'History saved successfully',
        ]);
    }
}
