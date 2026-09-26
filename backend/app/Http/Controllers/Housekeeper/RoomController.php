<?php

namespace App\Http\Controllers\Housekeeper;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;

class RoomController extends Controller
{
    public function index(): JsonResponse
    {
        $rooms = Room::with('roomType')
            ->orderBy('room_number')
            ->get();

        return response()->json($rooms);
    }
}
