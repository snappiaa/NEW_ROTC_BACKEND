<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'cadet_id' => 'required|string|unique:users,cadet_id',
                'designation' => 'required|string|max:255',
                'course_year' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'cadet_id' => $validated['cadet_id'],
                'designation' => $validated['designation'],
                'course_year' => $validated['course_year'],
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => 'cadet', // Default role
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'cadet_id' => $user->cadet_id,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
