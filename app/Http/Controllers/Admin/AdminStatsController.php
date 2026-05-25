<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Illuminate\Http\JsonResponse;

class AdminStatsController extends Controller
{
    public function __invoke(AdminStatsService $stats): JsonResponse
    {
        return response()->json($stats->counts());
    }
}
