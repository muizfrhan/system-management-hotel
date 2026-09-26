<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 100) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Header Idempotency-Key wajib diisi dan maksimal 100 karakter.',
            ]);
        }

        $validated = $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:254',
            'phone' => 'required|string|max:20',
            'check_in_date' => 'required|date|after_or_equal:today|before_or_equal:'.today()->addDays(365)->toDateString(),
            'check_out_date' => 'required|date|after:check_in_date|before_or_equal:'.today()->addDays(366)->toDateString(),
            'number_of_guests' => 'required|integer|min:1|max:20',
            'special_requests' => 'nullable|string|max:2000',
        ]);

        $validated['idempotency_key'] = $idempotencyKey;

        $roomType = RoomType::find($validated['room_type_id']);

        if (! $roomType || ! $roomType->is_active) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Tipe kamar ini tidak tersedia untuk dipesan.',
            ]);
        }

        if ($validated['number_of_guests'] > $roomType->capacity) {
            throw ValidationException::withMessages([
                'number_of_guests' => "Jumlah tamu melebihi kapasitas kamar (maksimal {$roomType->capacity} tamu).",
            ]);
        }

        $reservation = $this->reservationService->createOnlineBooking($validated);

        return response()->json([
            'message' => 'Pemesanan berhasil! Silakan tunggu konfirmasi.',
            'reservation_code' => $reservation->reservation_code,
            'reservation' => [
                'reservation_code' => $reservation->reservation_code,
                'status' => $reservation->status,
                'check_in_date' => $reservation->check_in_date->toDateString(),
                'check_out_date' => $reservation->check_out_date->toDateString(),
                'number_of_guests' => $reservation->number_of_guests,
                'total_price' => $reservation->total_price,
                'room_number' => $reservation->room?->room_number,
                'room_type_name' => $reservation->room?->roomType?->name ?? $roomType->name,
                'guest_name' => $reservation->guest?->name,
            ],
        ], $reservation->wasRecentlyCreated ? 201 : 200);
    }
}
