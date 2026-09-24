<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class TrackBookingController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function show(string $code): JsonResponse
    {
        $reservation = Reservation::with(['guest', 'room.roomType', 'charges', 'payments'])
            ->where('reservation_code', $code)
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Kode reservasi tidak ditemukan.'], 404);
        }

        return response()->json($reservation);
    }

    public function cancel(string $code): JsonResponse
    {
        $reservation = Reservation::where('reservation_code', $code)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Kode reservasi tidak ditemukan.'], 404);
        }

        try {
            $this->reservationService->cancelReservation($reservation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Reservasi berhasil dibatalkan.']);
    }
}
