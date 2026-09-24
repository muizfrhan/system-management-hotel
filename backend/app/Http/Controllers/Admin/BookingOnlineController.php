<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class BookingOnlineController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function index(): JsonResponse
    {
        $bookings = Reservation::with(['guest', 'room.roomType'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($bookings);
    }

    public function confirm(Reservation $reservation): JsonResponse
    {
        $reservation = $this->reservationService->confirmBooking($reservation);

        return response()->json([
            'message' => 'Booking berhasil dikonfirmasi.',
            'reservation' => $reservation,
        ]);
    }

    public function reject(Reservation $reservation): JsonResponse
    {
        $reservation = $this->reservationService->rejectBooking($reservation);

        return response()->json([
            'message' => 'Booking berhasil ditolak.',
            'reservation' => $reservation,
        ]);
    }
}
