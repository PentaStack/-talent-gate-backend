<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AvatarUploadController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:5120'],
        ]);

        $user = $request->user();
        $file = $request->file('image');

        if ($user->role === 'candidate') {
            $profile = $user->candidateProfile
                ?? CandidateProfile::create(['user_id' => $user->id]);

            // Delete old avatar from Cloudinary if it exists
            if ($profile->avatar_url) {
                $this->cloudinary->deleteByUrl($profile->avatar_url, 'image');
            }

            $url = $this->cloudinary->uploadImage(
                $file,
                'talent-gate/avatars',
                "candidate-{$user->id}"
            );

            $profile->avatar_url = $url;
            $profile->save();

            return response()->json(['data' => ['avatar_url' => $url]]);
        }

        // Employer logo
        $profile = $user->employerProfile
            ?? EmployerProfile::create(['user_id' => $user->id, 'company_name' => '']);

        if ($profile->logo_url) {
            $this->cloudinary->deleteByUrl($profile->logo_url, 'image');
        }

        $url = $this->cloudinary->uploadImage(
            $file,
            'talent-gate/logos',
            "employer-{$user->id}"
        );

        $profile->logo_url = $url;
        $profile->save();

        return response()->json(['data' => ['logo_url' => $url]]);
    }
}
