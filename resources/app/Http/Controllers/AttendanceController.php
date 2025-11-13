<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Cadet;
use App\Models\AttendanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Get attendance records for specific date
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'status' => 'nullable|in:present,late,absent',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $date = $request->input('date');
        $perPage = $request->input('per_page', 30);

        $query = AttendanceRecord::with('cadet')
            ->where('attendance_date', $date);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Search by cadet_id or name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('cadet', function ($q) use ($search) {
                $q->where('cadet_id', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $records = $query->orderBy('attendance_time', 'asc')->paginate($perPage);

        // Get statistics
        $totalCadets = Cadet::count();
        $presentCount = AttendanceRecord::where('attendance_date', $date)
            ->where('status', 'present')->count();
        $lateCount = AttendanceRecord::where('attendance_date', $date)
            ->where('status', 'late')->count();
        $recordedCount = $presentCount + $lateCount;
        $absentCount = $totalCadets - $recordedCount;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'records' => $records->items(),
                'statistics' => [
                    'total' => $totalCadets,
                    'present' => $presentCount,
                    'late' => $lateCount,
                    'absent' => $absentCount,
                ],
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'last_page' => $records->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Get today's attendance summary
     */
    public function today(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $totalCadets = Cadet::count();

        $presentCount = AttendanceRecord::where('attendance_date', $today)
            ->where('status', 'present')->count();
        $lateCount = AttendanceRecord::where('attendance_date', $today)
            ->where('status', 'late')->count();
        $recordedCount = $presentCount + $lateCount;
        $absentCount = $totalCadets - $recordedCount;

        $attendanceRate = $totalCadets > 0
            ? round(($recordedCount / $totalCadets) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'total_cadets' => $totalCadets,
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'attendance_rate' => $attendanceRate,
            ],
        ], 200);
    }

    /**
     * Record new attendance
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cadet_id' => 'required|exists:cadets,cadet_id',
            'status' => 'required|in:present,late,absent',
            'attendance_date' => 'required|date',
            'attendance_time' => 'required|date_format:H:i:s',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if already recorded
        $exists = AttendanceRecord::where('cadet_id', $request->cadet_id)
            ->where('attendance_date', $request->attendance_date)
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already recorded for this cadet today',
            ], 422);
        }

        // Create timestamp
        $timestamp = Carbon::parse($request->attendance_date . ' ' . $request->attendance_time);

        $record = AttendanceRecord::create([
            'cadet_id' => $request->cadet_id,
            'status' => $request->status,
            'timestamp' => $timestamp,
            'attendance_date' => $request->attendance_date,
            'attendance_time' => $request->attendance_time,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully',
            'data' => [
                'record' => $record->load('cadet'),
            ],
        ], 201);
    }

    /**
     * Export attendance as CSV
     */
    public function export(Request $request)
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
            ->orderBy('attendance_time', 'asc')
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

        $filename = 'attendance-' . $request->date . '.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
