<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * @return Collection<int, User>
     */
    public function list(): Collection
    {
        return User::query()
            ->orderBy('name')
            ->get();
    }

    public function ban(User $actor, User $target): User
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages([
                'user' => ['You cannot ban your own account.'],
            ]);
        }

        if ($target->role === 'admin') {
            throw ValidationException::withMessages([
                'user' => ['Admin accounts cannot be banned.'],
            ]);
        }

        $target->update(['banned' => true]);

        return $target->fresh();
    }

    public function updateRole(User $actor, User $target, string $role): User
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages([
                'role' => ['You cannot change your own role.'],
            ]);
        }

        $target->update(['role' => $role]);

        return $target->fresh();
    }

    /**
     * @return array{id: int, name: string, email: string, role: string, banned: bool, created_at: string|null}
     */
    public function toArray(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'banned' => (bool) $user->banned,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
