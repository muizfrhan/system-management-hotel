<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $from = $filters['from'] ?? now()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now()->endOfMonth()->toDateString();
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();

        if ($end->lessThan($start) || $start->diffInDays($end) > 366) {
            throw ValidationException::withMessages([
                'to' => 'Rentang laporan harus berurutan dan maksimal 366 hari.',
            ]);
        }

        $report = $this->reportService->generate($from, $to);

        return response()->json($report);
    }
}
