<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * GET /files/{fileId}/comments
     */
    public function index(string $fileId)
    {
        return response()->json(
            Comment::where('file_id', $fileId)
                ->with('user')
                ->latest()
                ->get()
        );
    }

    /**
     * POST /files/{fileId}/comments
     */
    public function store(Request $request, string $fileId)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'content' => 'required|string',
        ]);

        $comment = Comment::create([
            'file_id' => $fileId,
            'user_id' => $validated['user_id'],
            'content' => $validated['content'],
        ]);

        return response()->json($comment, 201);
    }

    /**
     * PUT /comments/{id}
     */
    public function update(Request $request, string $id)
    {
        $comment = Comment::findOrFail($id);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);

        return response()->json($comment);
    }

    /**
     * DELETE /comments/{id}
     */
    public function destroy(string $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
