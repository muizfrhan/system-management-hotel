<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
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
        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');

        $roomTypes = RoomType::with('facilities')
            ->where('is_active', true)
            ->get()
            ->map(function ($roomType) use ($checkIn, $checkOut) {
                if ($checkIn && $checkOut && strtotime($checkOut) > strtotime($checkIn)) {
                    $roomType->available_rooms_count = $this->reservationService
                        ->countAvailableRooms($roomType->id, $checkIn, $checkOut);
                } else {
                    $roomType->available_rooms_count = $roomType->rooms()
                        ->where('status', 'available')
                        ->count();
                }

                return $roomType;
            });

        return response()->json($roomTypes);
    }

    public function roomTypeDetail(Request $request, RoomType $roomType): JsonResponse
    {
        $roomType->load('facilities');

        $checkIn = $request->query('check_in');
        $checkOut = $request->query('check_out');

        if ($checkIn && $checkOut && strtotime($checkOut) > strtotime($checkIn)) {
            $availableRooms = $this->reservationService
                ->countAvailableRooms($roomType->id, $checkIn, $checkOut);
        } else {
            $availableRooms = $roomType->rooms()->where('status', 'available')->count();
        }

        return response()->json([
            'room_type' => $roomType,
            'available_rooms' => $availableRooms,
        ]);
    }
}
