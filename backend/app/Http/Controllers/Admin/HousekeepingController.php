<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\RoomStatusService;
use Illuminate\Http\JsonResponse;

class HousekeepingController extends Controller
{
    public function __construct(
        protected RoomStatusService $roomStatusService
    ) {}

    public function index(): JsonResponse
    {
        $rooms = Room::with('roomType')
            ->where('status', 'cleaning')
            ->orderBy('room_number')
            ->get();

        return response()->json($rooms);
    }

    public function done(Room $room): JsonResponse
    {
        $room = $this->roomStatusService->markAsAvailable($room);

        return response()->json([
            'message' => 'Kamar ' . $room->room_number . ' selesai dibersihkan.',
            'room' => $room,
        ]);
    }
}
