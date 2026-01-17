<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    /**
     * GET /organizations
     */
    public function index(Request $request)
    {
        return response()->json(
            Organization::query()
                ->latest()
                ->paginate($request->get('per_page', 20))
        );
    }

    /**
     * POST /organizations
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'nullable|string|max:50',
        ]);

        $organization = Organization::create($validated);

        return response()->json($organization, 201);
    }

    /**
     * GET /organizations/{id}
     */
    public function show(string $id)
    {
        $organization = Organization::findOrFail($id);

        return response()->json($organization);
    }

    /**
     * PUT /organizations/{id}
     */
    public function update(Request $request, string $id)
    {
        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'plan' => 'nullable|string|max:50',
        ]);

        $organization->update($validated);

        return response()->json($organization);
    }

    /**
     * DELETE /organizations/{id}
     */
    public function destroy(string $id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();

        return response()->json(['message' => 'Organization deleted']);
    }
}
