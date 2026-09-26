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
        $reservation = Reservation::with(['guest', 'room.roomType'])
            ->where('reservation_code', $code)
            ->first();

        if (! $reservation) {
            return response()->json(['message' => 'Kode reservasi tidak ditemukan.'], 404);
        }

        return response()->json([
            'reservation_code' => $reservation->reservation_code,
            'status' => $reservation->status,
            'check_in_date' => $reservation->check_in_date->toDateString(),
            'check_out_date' => $reservation->check_out_date->toDateString(),
            'number_of_guests' => $reservation->number_of_guests,
            'total_price' => $reservation->total_price,
            'guest' => [
                'name' => $reservation->guest?->name,
            ],
            'room' => [
                'room_number' => $reservation->room?->room_number,
                'room_type' => [
                    'name' => $reservation->room?->roomType?->name,
                ],
            ],
        ]);
    }

    public function cancel(string $code): JsonResponse
    {
        $reservation = Reservation::where('reservation_code', $code)->first();

        if (! $reservation) {
            return response()->json(['message' => 'Kode reservasi tidak ditemukan.'], 404);
        }

        if ($reservation->status === 'cancelled') {
            return response()->json(['message' => 'Reservasi sudah dibatalkan.']);
        }

        if ($reservation->check_in_date->isPast()) {
            return response()->json(['message' => 'Reservasi yang sudah memiliki tanggal check-in tidak dapat dibatalkan secara publik.'], 409);
        }

        $this->reservationService->cancelReservation($reservation);

        return response()->json(['message' => 'Reservasi berhasil dibatalkan.']);
    }
}
