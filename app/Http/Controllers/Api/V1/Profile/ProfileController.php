<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\CandidateProfileResource;
use App\Http\Resources\EmployerProfileResource;
use App\Http\Resources\PublicCandidateProfileResource;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['candidateProfile', 'employerProfile']);

        if ($user->role === 'candidate') {
            $profile = $user->candidateProfile ?? CandidateProfile::create(['user_id' => $user->id]);
            return response()->json(['data' => new CandidateProfileResource($profile)]);
        }

        $profile = $user->employerProfile ?? EmployerProfile::create(['user_id' => $user->id, 'company_name' => '']);
        return response()->json(['data' => new EmployerProfileResource($profile)]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'candidate') {
            $profile = $user->candidateProfile ?? CandidateProfile::create(['user_id' => $user->id]);
            $profile->fill($request->only(['bio', 'skills', 'experience_level']));
            $profile->save();

            if ($request->has('name')) {
                $user->name = $request->input('name');
                $user->save();
            }

            return response()->json(['data' => new CandidateProfileResource($profile), 'message' => 'Profile updated']);
        }

        $profile = $user->employerProfile ?? EmployerProfile::create(['user_id' => $user->id, 'company_name' => '']);
        $profile->fill($request->only(['company_name', 'website', 'description']));
        $profile->save();

        if ($request->has('name')) {
            $user->name = $request->input('name');
            $user->save();
        }

        return response()->json(['data' => new EmployerProfileResource($profile), 'message' => 'Profile updated']);
    }

    public function showPublic(string $userId): JsonResponse
    {
        if (! is_numeric($userId)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($user->role === 'candidate') {
            $profile = $user->candidateProfile ?? CandidateProfile::create(['user_id' => $user->id]);
            return response()->json([
                'data' => [
                    'id' => $profile->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role' => 'candidate',
                    'bio' => $profile->bio,
                    'skills' => $profile->skills,
                    'experience_level' => $profile->experience_level,
                    'avatar_url' => $profile->avatar_full_url,
                ]
            ]);
        }

        if ($user->role === 'employer') {
            $profile = $user->employerProfile ?? EmployerProfile::create(['user_id' => $user->id, 'company_name' => $user->name]);
            return response()->json([
                'data' => [
                    'id' => $profile->id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'role' => 'employer',
                    'company_name' => $profile->company_name,
                    'website' => $profile->website,
                    'description' => $profile->description,
                    'logo_url' => $profile->logo_full_url,
                ]
            ]);
        }

        return response()->json(['message' => 'Not found'], 404);
    }

    public function resumeLink(Request $request, string $userId): JsonResponse
    {
        if (! is_numeric($userId)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = User::find($userId);
        if (! $user || $user->role !== 'candidate') {
            return response()->json(['message' => 'Not found'], 404);
        }

        $profile = $user->candidateProfile;
        if (! $profile || ! $profile->resume_url) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $url = Storage::disk(config('filesystems.default'))->temporaryUrl($profile->resume_url, now()->addMinutes(60));
        return response()->json(['data' => ['url' => $url]]);
    }
}
