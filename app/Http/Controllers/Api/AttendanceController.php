<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use App\Models\Cadet;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Get attendance records for a specific date (with filters)
    public function index(Request $request)
    {
        $validated = $request->validate([
            'date'    => 'required|date',
            'status'  => 'nullable|in:present,late,absent',
            'page'    => 'nullable|integer|min:1',
            'perpage' => 'nullable|integer|min:1|max:100',
            'search'  => 'nullable|string',
        ]);

        $perPage = $request->input('perpage', 30);
        $query = AttendanceRecord::with('cadet')->whereDate('timestamp', $validated['date']);

        if ($request->filled('status')) {
            $query->where('status', $validated['status']);
        }

        if ($request->filled('search')) {
            $query->whereHas('cadet', function ($q) use ($validated) {
                $q->where('name', 'like', '%' . $validated['search'] . '%');
            });
        }

        $records = $query->orderBy('timestamp', 'desc')->paginate($perPage);
        $totalCadets = Cadet::count();
        $dateRecords = AttendanceRecord::whereDate('timestamp', $validated['date'])->get();

        $present = $dateRecords->where('status', 'present')->count();
        $late    = $dateRecords->where('status', 'late')->count();
        $absent  = $totalCadets - $dateRecords->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'date'        => $validated['date'],
                'records'     => $records->items(),
                'statistics'  => [
                    'total' => $dateRecords->count(),
                    'present' => $present,
                    'late' => $late,
                    'absent' => $absent
                ]
            ]
        ]);
    }

    // Get today's attendance summary
    public function today()
    {
        $today = Carbon::now()->format('Y-m-d');
        $totalCadets = Cadet::count();
        $dateRecords = AttendanceRecord::whereDate('timestamp', $today)->get();

        $present = $dateRecords->where('status', 'present')->count();
        $late    = $dateRecords->where('status', 'late')->count();
        $absent  = $totalCadets - $dateRecords->count();
        $rate    = ($totalCadets > 0) ? round(($dateRecords->count() / $totalCadets) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'totalcadets' => $totalCadets,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'attendancerate' => $rate,
            ]
        ]);
    }

    // Get dashboard statistics
    public function stats()
    {
        $totalCadets = Cadet::count();
        $today = Carbon::now()->format('Y-m-d');
        $dateRecords = AttendanceRecord::whereDate('timestamp', $today)->get();

        $present = $dateRecords->where('status', 'present')->count();
        $late = $dateRecords->where('status', 'late')->count();
        $absent = $totalCadets - $dateRecords->count();

        $attendanceRate = ($totalCadets > 0)
            ? round(($dateRecords->count() / $totalCadets) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'totalCadets' => $totalCadets,
                'presentToday' => $present,
                'attendanceRate' => $attendanceRate
            ]
        ]);
    }

    // Get recent attendance activity
    public function recent()
    {
        $today = Carbon::now()->format('Y-m-d');

        $records = AttendanceRecord::with('cadet')
            ->whereDate('timestamp', $today)
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get();

        $recentActivity = $records->map(function ($record) {
            return [
                'id' => $record->id,
                'cadetName' => $record->cadet->name,
                'cadetId' => $record->cadet->cadetid,
                'status' => $record->status,
                'time' => Carbon::parse($record->timestamp)->format('h:i A')
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $recentActivity
        ]);
    }

    // Record new attendance
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cadetid'        => 'required|string|exists:cadets,cadetid',
            'status'         => 'required|in:present,late,absent',
            'attendancedate' => 'required|date',
            'attendancetime' => 'required',
        ]);

        // Already checked in?
        $existing = AttendanceRecord::where('cadetid', $validated['cadetid'])
            ->whereDate('timestamp', $validated['attendancedate'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this cadet today'
            ], 422);
        }

        $timestamp = Carbon::parse($validated['attendancedate'] . ' ' . $validated['attendancetime']);

        // 08:30 AM cutoff
        $cutoff = Carbon::parse($validated['attendancedate'] . ' 08:30:00');
        $expectedStatus = $timestamp->lte($cutoff) ? 'present' : 'late';

        if ($validated['status'] !== $expectedStatus && $validated['status'] !== 'absent') {
            $validated['status'] = $expectedStatus;
        }

        $record = AttendanceRecord::create([
            'cadetid'        => $validated['cadetid'],
            'status'         => $validated['status'],
            'timestamp'      => $timestamp,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data'    => ['record' => $record]
        ], 201);
    }

    // Export attendance as CSV
    public function export(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $records = AttendanceRecord::with('cadet')
            ->whereDate('timestamp', $request->date)
            ->orderBy('timestamp', 'asc')
            ->get();

        $csv = "Name,Cadet ID,Status,Time\n";

        foreach ($records as $record) {
            $time = Carbon::parse($record->timestamp)->format('h:i A');
            $csv .= sprintf(
                '%s,%s,%s,%s\n',
                $record->cadet->name,
                $record->cadet->cadetid,
                ucfirst($record->status),
                $time
            );
        }

        $filename = 'attendance-' . $request->date . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }
}
