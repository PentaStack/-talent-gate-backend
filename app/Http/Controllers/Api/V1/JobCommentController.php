<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobCommentController extends Controller
{
    public function index(Job $job): JsonResponse
    {
        $comments = $job->comments()
            ->where('is_hidden', false)
            ->with('user:id,name,role') // we may need avatar here, let's load what's available
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $comments->map(function (JobComment $comment) {
                return [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'created_at' => $comment->created_at->toIso8601String(),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'role' => $comment->user->role,
                        'avatar_url' => $comment->user->candidateProfile?->avatar_url ?? $comment->user->employerProfile?->logo_full_url,
                    ],
                    'is_owner' => request()->user()?->id === $comment->user_id,
                ];
            }),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function store(Request $request, Job $job): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $comment = $job->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $comment->load('user');

        return response()->json([
            'data' => [
                'id' => $comment->id,
                'body' => $comment->body,
                'created_at' => $comment->created_at->toIso8601String(),
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                    'avatar_url' => $comment->user->candidateProfile?->avatar_url ?? $comment->user->employerProfile?->logo_full_url,
                ],
                'is_owner' => true,
            ]
        ], 201);
    }

    public function destroy(JobComment $comment): JsonResponse
    {
        if ($comment->user_id !== request()->user()->id && !request()->user()->hasRole('admin')) {
            abort(403);
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}
