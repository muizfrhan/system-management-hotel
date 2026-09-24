<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::remember('dashboard_stats', 300, function () {
            $totalRooms = Room::count();
            $available = Room::where('status', 'available')->count();
            $occupied = Room::where('status', 'occupied')->count();
            $reserved = Room::where('status', 'reserved')->count();
            $cleaning = Room::where('status', 'cleaning')->count();
            $maintenance = Room::where('status', 'maintenance')->count();

            $todayCheckIn = Reservation::where('status', 'confirmed')
                ->whereDate('check_in_date', today())
                ->count();

            $todayCheckOut = Reservation::where('status', 'checked_in')
                ->whereDate('check_out_date', today())
                ->count();

            $pendingBookings = Reservation::where('status', 'pending')->count();

            $monthlyRevenue = Payment::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount');

            $recentReservations = Reservation::with(['guest', 'room.roomType'])
                ->latest()
                ->take(5)
                ->get();

            $dailyRevenue = collect(range(6, 0))->map(function ($daysAgo) {
                $date = Carbon::today()->subDays($daysAgo);
                return [
                    'label' => $date->translatedFormat('d M'),
                    'revenue' => (float) Payment::where('status', 'paid')
                        ->whereDate('paid_at', $date)
                        ->sum('amount'),
                    'reservations' => Reservation::whereDate('created_at', $date)->count(),
                ];
            })->values();

            $monthlyChart = collect(range(5, 0))->map(function ($monthsAgo) {
                $date = Carbon::today()->subMonths($monthsAgo);
                return [
                    'label' => $date->translatedFormat('M Y'),
                    'revenue' => (float) Payment::where('status', 'paid')
                        ->whereMonth('paid_at', $date->month)
                        ->whereYear('paid_at', $date->year)
                        ->sum('amount'),
                    'reservations' => Reservation::whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->count(),
                ];
            })->values();

            $statusBreakdown = [
                'pending' => Reservation::where('status', 'pending')->count(),
                'confirmed' => Reservation::where('status', 'confirmed')->count(),
                'checked_in' => Reservation::where('status', 'checked_in')->count(),
                'checked_out' => Reservation::where('status', 'checked_out')->count(),
                'cancelled' => Reservation::where('status', 'cancelled')->count(),
            ];

            return [
                'rooms' => [
                    'total' => $totalRooms,
                    'available' => $available,
                    'occupied' => $occupied,
                    'reserved' => $reserved,
                    'cleaning' => $cleaning,
                    'maintenance' => $maintenance,
                ],
                'today_check_in' => $todayCheckIn,
                'today_check_out' => $todayCheckOut,
                'pending_bookings' => $pendingBookings,
                'monthly_revenue' => $monthlyRevenue,
                'recent_reservations' => $recentReservations,
                'daily_revenue' => $dailyRevenue,
                'monthly_chart' => $monthlyChart,
                'status_breakdown' => $statusBreakdown,
            ];
        });

        return response()->json($data);
    }
}
