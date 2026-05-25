<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmployerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployerAnalyticsController extends Controller
{
    public function __invoke(Request $request, EmployerAnalyticsService $analytics): JsonResponse
    {
        return response()->json($analytics->forEmployer($request->user()));
    }
}
