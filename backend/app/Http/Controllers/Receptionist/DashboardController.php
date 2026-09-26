<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $today = today()->toDateString();

        $arrivals = Reservation::with(['guest', 'room.roomType', 'payments', 'charges'])
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', '<=', $today)
            ->orderBy('check_in_date')
            ->limit(100)
            ->get();

        $departures = Reservation::with(['guest', 'room.roomType', 'payments', 'charges'])
            ->where('status', 'checked_in')
            ->orderBy('check_out_date')
            ->limit(100)
            ->get();

        $roomCounts = Room::query()
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $roomTotal = Room::count();

        $pendingCount = Reservation::where('status', 'pending')->count();
        $pendingReservations = Reservation::with(['guest', 'room.roomType'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $recentActivity = Reservation::with(['guest', 'room.roomType'])
            ->latest()
            ->take(6)
            ->get();

        $todayDeparturesCount = Reservation::where('status', 'checked_in')
            ->whereDate('check_out_date', '<=', $today)
            ->count();

        return response()->json([
            'today' => $today,
            'arrivals' => $arrivals,
            'departures' => $departures,
            'today_departures_count' => $todayDeparturesCount,
            'rooms' => [
                'total' => $roomTotal,
                'available' => $roomCounts->get('available', 0),
                'occupied' => $roomCounts->get('occupied', 0),
                'reserved' => $roomCounts->get('reserved', 0),
                'cleaning' => $roomCounts->get('cleaning', 0),
                'maintenance' => $roomCounts->get('maintenance', 0),
            ],
            'pending_count' => $pendingCount,
            'pending_reservations' => $pendingReservations,
            'recent_activity' => $recentActivity,
        ]);
    }
}
