<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ResumeUploadController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:25600'],
        ]);

        $user = $request->user();
        if ($user->role !== 'candidate') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $profile = $user->candidateProfile
            ?? CandidateProfile::create(['user_id' => $user->id]);

        // Delete old resume from Cloudinary
        if ($profile->resume_url) {
            $this->cloudinary->deleteByUrl($profile->resume_url, 'raw');
        }

        $url = $this->cloudinary->uploadDocument(
            $request->file('file'),
            'talent-gate/resumes',
            "resume-{$user->id}"
        );

        $profile->resume_url = $url;
        $profile->save();

        return response()->json([
            'data'    => ['resume_url' => $url],
            'message' => 'Resume uploaded',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'candidate') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $profile = $user->candidateProfile;
        if (! $profile || ! $profile->resume_url) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->cloudinary->deleteByUrl($profile->resume_url, 'raw');
        $profile->resume_url = null;
        $profile->save();

        return response()->json(['message' => 'Resume removed']);
    }
}
