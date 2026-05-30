<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobComment::with(['user', 'job'])->latest();

        if ($request->query('filter') === 'hidden') {
            $query->where('is_hidden', true);
        }

        $comments = $query->paginate(30);

        return response()->json([
            'data' => $comments->map(function (JobComment $comment) {
                return [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'is_hidden' => $comment->is_hidden,
                    'created_at' => $comment->created_at->toIso8601String(),
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'job' => [
                        'id' => $comment->job->id,
                        'title' => $comment->job->title,
                    ],
                ];
            }),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

    public function hide(JobComment $comment): JsonResponse
    {
        $comment->update(['is_hidden' => !$comment->is_hidden]);

        return response()->json([
            'message' => $comment->is_hidden ? 'Comment hidden.' : 'Comment unhidden.',
            'data' => [
                'id' => $comment->id,
                'is_hidden' => $comment->is_hidden,
            ]
        ]);
    }

    public function destroy(JobComment $comment): JsonResponse
    {
        $comment->forceDelete();

        return response()->json(null, 204);
    }
}
