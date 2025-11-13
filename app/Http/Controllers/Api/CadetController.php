<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cadet;


class CadetController extends Controller
{
    // Get a list of cadets with pagination
    public function index(Request $request)
{
    $perPage = $request->input('per_page', 10);  // Changed from perpage
    $cadets = Cadet::orderBy('cadet_id', 'asc')->paginate($perPage);  // Changed from cadetid

    return response()->json([
        'success' => true,
        'data' => [
            'cadets' => $cadets->items(),
            'pagination' => [
                'current_page' => $cadets->currentPage(),
                'per_page' => $cadets->perPage(),
                'total' => $cadets->total(),
                'last_page' => $cadets->lastPage(),
                'from' => $cadets->firstItem(),
                'to' => $cadets->lastItem()
            ]
        ]
    ]);
}


    // Get cadet count
    public function count()
    {
        $total = Cadet::count();
        $male = Cadet::where('sex', 'Male')->count();
        $female = Cadet::where('sex', 'Female')->count();
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'male' => $male,
                'female' => $female
            ]
        ]);
    }

    // Show one cadet by cadetId
    public function show($cadetId)
    {
        $cadet = Cadet::where('cadetid', $cadetId)->first();
        if (!$cadet) {
            return response()->json([
                'success' => false,
                'message' => 'Cadet not found'
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [ 'cadet' => $cadet ]
        ]);
    }

    // Add a new cadet
    public function store(Request $request)
{
    $validated = $request->validate([
        'cadet_id'    => 'required|unique:cadets,cadet_id',  // Changed from cadetid
        'name'        => 'required|string',
        'designation' => 'required|string',
        'course_year' => 'required|string',                  // Changed from courseyear
        'sex'         => 'required|in:Male,Female'
    ]);

    $cadet = Cadet::create($validated);

    return response()->json([
        'success' => true,
        'message' => 'Cadet created successfully',
        'data' => $cadet
    ], 201);
}


    // Update a cadet by id
    public function update(Request $request, $id)
    {
        $cadet = Cadet::findOrFail($id);
        $validated = $request->validate([
            'name'         => 'sometimes|string',
            'designation'  => 'sometimes|string',
            'courseyear'   => 'sometimes|string',
            'sex'          => 'sometimes|in:Male,Female'
        ]);
        $cadet->update($validated);
        return response()->json([
            'success' => true,
            'message' => 'Cadet updated successfully',
            'data'    => [ 'cadet' => $cadet ]
        ]);
    }

    // Delete a cadet by id
    public function destroy($id)
    {
        $cadet = Cadet::findOrFail($id);
        $cadet->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cadet deleted successfully'
        ]);
    }
}
