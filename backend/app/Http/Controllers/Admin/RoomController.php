<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::with('roomType');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        $rooms = $query->orderBy('room_number')->get();
        return response()->json($rooms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => 'required|string|max:20|unique:rooms',
            'floor' => 'required|string|max:20',
            'status' => 'in:available,occupied,reserved,cleaning,maintenance',
        ]);

        $room = Room::create($validated);
        return response()->json($room->load('roomType'), 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => 'sometimes|exists:room_types,id',
            'room_number' => 'sometimes|string|max:20|unique:rooms,room_number,' . $room->id,
            'floor' => 'sometimes|string|max:20',
            'status' => 'in:available,occupied,reserved,cleaning,maintenance',
        ]);

        $room->update($validated);
        return response()->json($room->load('roomType'));
    }

    public function updateStatus(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,cleaning,maintenance',
        ]);

        $room->update($validated);
        return response()->json($room->load('roomType'));
    }

    public function destroy(Room $room): JsonResponse
    {
        $room->delete();
        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }
}
