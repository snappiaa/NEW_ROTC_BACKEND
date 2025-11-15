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
        $perPage = $request->input('per_page', 10);
        $cadets = Cadet::orderBy('cadet_id', 'asc')->paginate($perPage);

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
        $cadet = Cadet::where('cadet_id', $cadetId)->first();

        if (!$cadet) {
            return response()->json([
                'success' => false,
                'message' => 'Cadet not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => ['cadet' => $cadet]
        ]);
    }

    // Add a new cadet
    public function store(Request $request)
    {
        // ✅ FIXED: Changed validation from 'designation' to 'company' and 'platoon'
        $validated = $request->validate([
            'cadet_id'    => 'required|unique:cadets,cadet_id',
            'name'        => 'required|string',
            'company'     => 'required|in:Alpha,Bravo,Charlie',  // ✅ NEW
            'platoon'     => 'required|in:1,2,3,4,5',            // ✅ NEW
            'course_year' => 'required|string',
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

        // ✅ FIXED: Changed validation from 'designation' to 'company' and 'platoon'
        $validated = $request->validate([
            'name'        => 'sometimes|string',
            'company'     => 'sometimes|in:Alpha,Bravo,Charlie',  // ✅ CHANGED
            'platoon'     => 'sometimes|in:1,2,3,4,5',            // ✅ NEW
            'course_year' => 'sometimes|string',
            'sex'         => 'sometimes|in:Male,Female'
        ]);

        $cadet->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cadet updated successfully',
            'data'    => ['cadet' => $cadet]
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
