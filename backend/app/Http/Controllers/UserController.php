<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * GET /users
     */
    public function index(Request $request)
    {
        return response()->json(
            User::query()
                ->latest()
                ->paginate($request->get('per_page', 50))
        );
    }

    /**
     * POST /users
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * GET /users/{id}
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    /**
     * PUT /users/{id}
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * DELETE /users/{id}
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
