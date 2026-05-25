<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(AdminUserService $users): JsonResponse
    {
        $data = $users->list()->map(fn (User $user) => $users->toArray($user));

        return response()->json(['data' => $data]);
    }

    public function ban(Request $request, User $user, AdminUserService $users): JsonResponse
    {
        $updated = $users->ban($request->user(), $user);

        return response()->json([
            'message' => 'User banned',
            'user' => $users->toArray($updated),
        ]);
    }

    public function update(Request $request, User $user, AdminUserService $users): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', 'in:admin,employer,candidate'],
        ]);

        $updated = $users->updateRole($request->user(), $user, $validated['role']);

        return response()->json([
            'message' => 'User role updated',
            'user' => $users->toArray($updated),
        ]);
    }
}
