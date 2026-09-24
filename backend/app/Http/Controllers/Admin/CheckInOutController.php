<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\CheckInOutService;
use Illuminate\Http\JsonResponse;

class CheckInOutController extends Controller
{
    public function __construct(
        protected CheckInOutService $checkInOutService
    ) {}

    public function index(): JsonResponse
    {
        $checkIns = Reservation::with(['guest', 'room.roomType'])
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', '<=', today())
            ->get();

        $checkOuts = Reservation::with(['guest', 'room.roomType'])
            ->where('status', 'checked_in')
            ->get();

        return response()->json([
            'check_ins' => $checkIns,
            'check_outs' => $checkOuts,
        ]);
    }

    public function checkIn(Reservation $reservation): JsonResponse
    {
        $reservation = $this->checkInOutService->processCheckIn($reservation);

        return response()->json([
            'message' => 'Check-in berhasil.',
            'reservation' => $reservation,
        ]);
    }

    public function checkOut(Reservation $reservation): JsonResponse
    {
        $reservation = $this->checkInOutService->processCheckOut($reservation);

        return response()->json([
            'message' => 'Check-out berhasil.',
            'reservation' => $reservation,
        ]);
    }
}
