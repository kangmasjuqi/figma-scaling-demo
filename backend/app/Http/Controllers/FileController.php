<?php
// app/Http/Controllers/FileController.php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function index(Request $request)
    {
        $query = File::with(['owner:id,name,email', 'organization:id,name']);
        
        // Pagination
        $perPage = $request->input('per_page', 20);
        
        // Filters
        if ($request->has('owner_id')) {
            $query->where('owner_id', $request->input('owner_id'));
        }
        
        if ($request->has('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }
        
        if ($request->has('search')) {
            $query->where('name', 'ILIKE', '%' . $request->input('search') . '%');
        }
        
        // Sorting
        $sortBy = $request->input('sort_by', 'last_modified');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        return response()->json($query->paginate($perPage));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:500',
            'owner_id' => 'required|uuid|exists:users,id',
            'organization_id' => 'required|uuid|exists:organizations,id',
            'is_public' => 'boolean',
            'metadata' => 'array'
        ]);
        
        $file = DB::transaction(function () use ($validated, $request) {
            $file = File::create($validated);
            
            // Log activity
            ActivityLog::create([
                'user_id' => $validated['owner_id'],
                'file_id' => $file->id,
                'action' => 'created',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $file;
        });
        
        return response()->json($file->load(['owner', 'organization']), 201);
    }
    
    public function show(string $id)
    {
        $file = File::with(['owner', 'organization', 'comments.user'])
            ->findOrFail($id);
        
        return response()->json($file);
    }
    
    public function update(Request $request, string $id)
    {
        $file = File::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:500',
            'is_public' => 'sometimes|boolean',
            'metadata' => 'sometimes|array'
        ]);
        
        DB::transaction(function () use ($file, $validated, $request) {
            $file->update($validated);
            $file->increment('version');
            $file->touch('last_modified');
            
            // Log activity
            ActivityLog::create([
                'user_id' => $file->owner_id,
                'file_id' => $file->id,
                'action' => 'edited',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        });
        
        return response()->json($file->fresh());
    }
    
    public function destroy(string $id)
    {
        $file = File::findOrFail($id);
        $file->delete();
        
        return response()->json(['message' => 'File deleted successfully']);
    }
    
    public function incrementView(Request $request, string $id)
    {
        $updated = File::where('id', $id)->increment('view_count');

        if (!$updated) {
            return response()->json(['ignored' => true], 200);
        }

        ActivityLog::create([
            'user_id' => $request->input('user_id'),
            'file_id' => $id,
            'action' => 'viewed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['ok' => true]);
    }
}