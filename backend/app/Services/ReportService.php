<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportService
{
    public function generate(string $from, string $to): array
    {
        $toEnd = Carbon::parse($to)->endOfDay();

        $totalReservations = Reservation::whereBetween('created_at', [$from, $toEnd])->count();
        $confirmedReservations = Reservation::whereBetween('created_at', [$from, $toEnd])
            ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->count();
        $cancelledReservations = Reservation::whereBetween('created_at', [$from, $toEnd])
            ->where('status', 'cancelled')->count();

        $totalRevenue = Payment::where('status', 'paid')
            ->whereBetween('paid_at', [$from, $toEnd])
            ->sum('amount');

        $recentPayments = Payment::with('reservation.guest')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $toEnd])
            ->latest('paid_at')
            ->take(20)
            ->get();

        $period = CarbonPeriod::create($from, $to);
        $dailyChart = collect($period)->map(function ($date) {
            return [
                'label' => $date->translatedFormat('d M'),
                'revenue' => (float) Payment::where('status', 'paid')
                    ->whereDate('paid_at', $date)
                    ->sum('amount'),
                'reservations' => Reservation::whereDate('created_at', $date)->count(),
            ];
        })->values();

        $statusBreakdown = [
            'pending' => Reservation::whereBetween('created_at', [$from, $toEnd])
                ->where('status', 'pending')->count(),
            'confirmed' => Reservation::whereBetween('created_at', [$from, $toEnd])
                ->whereIn('status', ['confirmed', 'checked_in', 'checked_out'])->count(),
            'cancelled' => Reservation::whereBetween('created_at', [$from, $toEnd])
                ->where('status', 'cancelled')->count(),
        ];

        return [
            'period' => ['from' => $from, 'to' => $to],
            'total_reservations' => $totalReservations,
            'confirmed_reservations' => $confirmedReservations,
            'cancelled_reservations' => $cancelledReservations,
            'total_revenue' => $totalRevenue,
            'recent_payments' => $recentPayments,
            'daily_chart' => $dailyChart,
            'status_breakdown' => $statusBreakdown,
        ];
    }
}
