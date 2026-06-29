<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CentralBugReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BugReportController extends Controller
{
    public function __construct(
        protected CentralBugReportService $bugReports,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->bugReports->list($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $data = $this->bugReports->submit($request, $validated);

        return response()->json([
            'data' => $data,
            'message' => __('bug_reports.submitted'),
        ], 201);
    }
}
