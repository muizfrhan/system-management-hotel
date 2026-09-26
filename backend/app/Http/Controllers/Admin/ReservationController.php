<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => 'nullable|string|in:pending,confirmed,checked_in,checked_out,cancelled',
            'date' => 'nullable|date_format:Y-m-d',
            'q' => 'nullable|string|max:100',
        ]);

        $query = Reservation::with(['guest', 'room.roomType', 'payments', 'charges']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date'])) {
            $query->whereDate('check_in_date', $filters['date']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($qq) use ($q) {
                $qq->where('reservation_code', 'like', "%{$q}%")
                    ->orWhereHas('guest', function ($g) use ($q) {
                        $g->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->orWhereHas('room', function ($r) use ($q) {
                        $r->where('room_number', 'like', "%{$q}%");
                    });
            });
        }

        $reservations = $query->latest()->paginate(10);

        return response()->json($reservations);
    }

    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 100) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Header Idempotency-Key wajib diisi dan maksimal 100 karakter.',
            ]);
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'nullable|email|max:254',
            'guest_phone' => 'nullable|string|max:20',
            'room_id' => 'required|integer|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today|before_or_equal:'.today()->addDays(365)->toDateString(),
            'check_out_date' => 'required|date|after:check_in_date|before_or_equal:'.today()->addDays(366)->toDateString(),
            'number_of_guests' => 'required|integer|min:1|max:20',
            'special_requests' => 'nullable|string|max:2000',
        ]);
        $validated['idempotency_key'] = $idempotencyKey;

        $reservation = $this->reservationService->createManualReservation($validated);

        return response()->json($reservation, $reservation->wasRecentlyCreated ? 201 : 200);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return response()->json($reservation->load(['guest', 'room.roomType', 'payments', 'charges']));
    }

    public function update(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today|before_or_equal:'.today()->addDays(365)->toDateString(),
            'check_out_date' => 'required|date|after:check_in_date|before_or_equal:'.today()->addDays(366)->toDateString(),
            'number_of_guests' => 'required|integer|min:1|max:20',
            'special_requests' => 'nullable|string|max:2000',
        ]);

        $reservation = $this->reservationService->updateReservation($reservation, $validated);

        return response()->json($reservation);
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->reservationService->cancelReservation($reservation);

        return response()->json(['message' => 'Reservasi berhasil dibatalkan.']);
    }
}
