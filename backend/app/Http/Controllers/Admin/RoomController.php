<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => 'nullable|string|in:available,occupied,reserved,cleaning,maintenance',
            'floor' => 'nullable|string|max:20',
            'room_type_id' => 'nullable|integer|exists:room_types,id',
        ]);

        $query = Room::with('roomType');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['floor'])) {
            $query->where('floor', $filters['floor']);
        }
        if (! empty($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
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
        Cache::forget('dashboard_stats');
        Cache::forget('room_types_list');

        return response()->json($room->load('roomType'), 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'room_type_id' => 'sometimes|integer|exists:room_types,id',
            'room_number' => 'sometimes|string|max:20|unique:rooms,room_number,'.$room->id,
            'floor' => 'sometimes|string|max:20',
            'status' => 'sometimes|in:available,occupied,reserved,cleaning,maintenance',
        ]);

        $updated = DB::transaction(function () use ($room, $validated) {
            $lockedRoom = Room::with('roomType')->lockForUpdate()->findOrFail($room->id);

            if (isset($validated['room_type_id']) && (int) $validated['room_type_id'] !== (int) $lockedRoom->room_type_id) {
                $newRoomType = RoomType::query()->lockForUpdate()->findOrFail($validated['room_type_id']);
                $hasCapacityConflict = $lockedRoom->reservations()
                    ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                    ->where('number_of_guests', '>', $newRoomType->capacity)
                    ->exists();

                if ($hasCapacityConflict) {
                    throw ValidationException::withMessages([
                        'room_type_id' => 'Tipe kamar baru tidak dapat menampung reservasi aktif.',
                    ]);
                }
            }

            if (isset($validated['status']) && $lockedRoom->status === 'occupied' && $validated['status'] !== 'occupied') {
                throw new ConflictHttpException('Kamar yang sedang terisi tidak dapat diubah langsung ke status lain.');
            }

            $lockedRoom->update($validated);

            return $lockedRoom;
        });

        Cache::forget('dashboard_stats');
        Cache::forget('room_types_list');

        return response()->json($updated->load('roomType'));
    }

    public function updateStatus(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,cleaning,maintenance',
        ]);

        $updated = DB::transaction(function () use ($room, $validated) {
            $lockedRoom = Room::with('roomType')->lockForUpdate()->findOrFail($room->id);

            if ($lockedRoom->status === 'occupied' && $validated['status'] !== 'occupied') {
                throw new ConflictHttpException('Kamar yang sedang terisi tidak dapat diubah langsung ke status lain.');
            }

            $lockedRoom->update($validated);

            return $lockedRoom;
        });

        Cache::forget('dashboard_stats');
        Cache::forget('room_types_list');

        return response()->json($updated->load('roomType'));
    }

    public function destroy(Room $room): JsonResponse
    {
        DB::transaction(function () use ($room) {
            $lockedRoom = Room::lockForUpdate()->findOrFail($room->id);

            if ($lockedRoom->reservations()->exists()) {
                throw new ConflictHttpException('Kamar yang memiliki riwayat reservasi tidak dapat dihapus.');
            }

            $lockedRoom->delete();
        });

        Cache::forget('dashboard_stats');
        Cache::forget('room_types_list');

        return response()->json(['message' => 'Kamar berhasil dihapus.']);
    }
}
