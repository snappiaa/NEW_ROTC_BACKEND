<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Cadet;
use Carbon\Carbon;

class ReportController extends Controller
{
    // Attendance report for date range
    public function index(Request $request)
    {
        $request->validate([
            'startdate' => 'required|date',
            'enddate'   => 'required|date|after_or_equal:startdate',
        ]);
        $start = Carbon::parse($request->startdate);
        $end   = Carbon::parse($request->enddate);

        $records = AttendanceRecord::whereBetween('attendancedate', [$start, $end])->get();
        $totalCadets = Cadet::count();
        $present = $records->where('status', 'present')->count();
        $late    = $records->where('status', 'late')->count();
        $absent  = $records->where('status', 'absent')->count();

        // Daily breakdown
        $daily = [];
        $date = $start->copy();
        while ($date <= $end) {
            $str = $date->format('Y-m-d');
            $dayRecords = $records->where('attendancedate', $str);
            $daily[] = [
                'date'      => $str,
                'present'   => $dayRecords->where('status', 'present')->count(),
                'late'      => $dayRecords->where('status', 'late')->count(),
                'absent'    => $totalCadets - $dayRecords->count(),
                'rate'      => $totalCadets > 0 ? round($dayRecords->count() / $totalCadets * 100, 2) : 0
            ];
            $date->addDay();
        }

        $avgRate = count($daily) ? round(array_sum(array_column($daily, 'rate')) / count($daily), 2) : 0;
        return response()->json([
            'success' => true,
            'data'    => [
                'totalCadets' => $totalCadets,
                'present'     => $present,
                'late'        => $late,
                'absent'      => $absent,
                'averageRate' => $avgRate,
                'daily'       => $daily,
            ]
        ]);
    }

    // Students by status for a specific day
  public function studentsByStatus(Request $request)
{
    $request->validate([
        'date'   => 'required|date',
        'status' => 'required|in:present,late,absent,all',
        'page'   => 'nullable|integer',
        'perpage'=> 'nullable|integer',
    ]);

    $date = $request->date;
    $perPage = $request->input('perpage', 30);
    $query = Cadet::query();

    if ($request->status === 'all') {
        $cadets = $query->orderBy('cadetid', 'asc')->paginate($perPage);
        $students = $cadets->map(function ($cadet) use ($date) {
            $rec = AttendanceRecord::where('cadetid', $cadet->cadetid)
                ->where('attendancedate', $date)
                ->first();
            return [
                'cadetid'     => $cadet->cadetid,
                'name'        => $cadet->name,
                'designation' => $cadet->designation,
                'courseyear'  => $cadet->courseyear,
                'status'      => $rec ? $rec->status : 'absent',
                'timestamp'   => $rec ? $rec->timestamp : null
            ];
        });
    } elseif ($request->status === 'absent') {
        $presentIds = AttendanceRecord::where('attendancedate', $date)
            ->pluck('cadetid');
        $cadets = $query->whereNotIn('cadetid', $presentIds)
            ->orderBy('cadetid', 'asc')
            ->paginate($perPage);
        $students = $cadets->map(function($cadet) {
            return [
                'cadetid'     => $cadet->cadetid,
                'name'        => $cadet->name,
                'designation' => $cadet->designation,
                'courseyear'  => $cadet->courseyear,
                'status'      => 'absent',
                'timestamp'   => null
            ];
        });
    } else {
        $records = AttendanceRecord::with('cadet')
            ->where('attendancedate', $date)
            ->where('status', $request->status)
            ->orderBy('timestamp', 'asc')
            ->paginate($perPage);
        $students = $records->map(function($rec) {
            return [
                'cadetid'     => $rec->cadet->cadetid ?? null,
                'name'        => $rec->cadet->name ?? null,
                'designation' => $rec->cadet->designation ?? null,
                'courseyear'  => $rec->cadet->courseyear ?? null,
                'status'      => $rec->status,
                'timestamp'   => $rec->timestamp,
            ];
        });
    }

    return response()->json([
        'success' => true,
        'data'    => [
            'date'    => $date,
            'status'  => $request->status,
            'students'=> $students,
        ]
    ]);
}


    // Download report as CSV for date range
    public function download(Request $request)
    {
        $request->validate([
            'startdate' => 'required|date',
            'enddate'   => 'required|date|after_or_equal:startdate',
        ]);
        $start = Carbon::parse($request->startdate);
        $end   = Carbon::parse($request->enddate);
        $totalCadets = Cadet::count();
        $records = AttendanceRecord::whereBetween('attendancedate', [$start, $end])->get();
        $csv = "Date,Total Cadets,Present,Late,Absent,Attendance Rate\n";
        $date = $start->copy();
        while ($date <= $end) {
            $str = $date->format('Y-m-d');
            $dayRecords = $records->where('attendancedate', $str);
            $present = $dayRecords->where('status', 'present')->count();
            $late = $dayRecords->where('status', 'late')->count();
            $absent = $totalCadets - $dayRecords->count();
            $rate = $totalCadets > 0 ? round($dayRecords->count() / $totalCadets * 100, 2) : 0;
            $csv .= sprintf('%s,%d,%d,%d,%d,%.2f\n', $str, $totalCadets, $present, $late, $absent, $rate);
            $date->addDay();
        }
        $filename = 'report-' . $request->startdate . '-to-' . $request->enddate . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }
}
