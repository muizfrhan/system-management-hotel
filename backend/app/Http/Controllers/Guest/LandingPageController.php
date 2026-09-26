<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService
    ) {}

    public function index(): JsonResponse
    {
        $setting = Setting::first();
        $roomTypes = RoomType::with('facilities')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'setting' => $setting,
            'room_types' => $roomTypes,
        ]);
    }

    public function roomTypes(Request $request): JsonResponse
    {
        $dates = $this->validateDates($request);
        $checkIn = $dates['check_in'] ?? null;
        $checkOut = $dates['check_out'] ?? null;

        $roomTypes = RoomType::with('facilities')
            ->where('is_active', true)
            ->get();

        $availableRoomCounts = Room::query()
            ->whereIn('status', ['available', 'reserved'])
            ->when($checkIn && $checkOut, function ($query) use ($checkIn, $checkOut) {
                $query->whereDoesntHave('reservations', function ($reservationQuery) use ($checkIn, $checkOut) {
                    $reservationQuery->whereNotIn('status', ['cancelled', 'checked_out'])
                        ->where('check_in_date', '<', $checkOut)
                        ->where('check_out_date', '>', $checkIn);
                });
            })
            ->select('room_type_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('room_type_id')
            ->pluck('aggregate', 'room_type_id');

        $roomTypes->each(function ($roomType) use ($availableRoomCounts) {
            $roomType->available_rooms_count = (int) ($availableRoomCounts[$roomType->id] ?? 0);
        });

        return response()->json($roomTypes);
    }

    public function roomTypeDetail(Request $request, RoomType $roomType): JsonResponse
    {
        if (! $roomType->is_active) {
            return response()->json(['message' => 'Tipe kamar tidak ditemukan.'], 404);
        }

        $roomType->load('facilities');

        $dates = $this->validateDates($request);
        $checkIn = $dates['check_in'] ?? null;
        $checkOut = $dates['check_out'] ?? null;

        if ($checkIn && $checkOut && strtotime($checkOut) > strtotime($checkIn)) {
            $availableRooms = $this->reservationService
                ->countAvailableRooms($roomType->id, $checkIn, $checkOut);
        } else {
            $availableRooms = $roomType->rooms()->whereIn('status', ['available', 'reserved'])->count();
        }

        return response()->json([
            'room_type' => $roomType,
            'available_rooms' => $availableRooms,
        ]);
    }

    protected function validateDates(Request $request): array
    {
        return $request->validate([
            'check_in' => 'nullable|required_with:check_out|date_format:Y-m-d|after_or_equal:today|before_or_equal:'.today()->addDays(365)->toDateString(),
            'check_out' => 'nullable|required_with:check_in|date_format:Y-m-d|after:check_in|before_or_equal:'.today()->addDays(366)->toDateString(),
        ]);
    }
}
