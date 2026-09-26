<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RoomStatusService
{
    public function markAsAvailable(Room $room): Room
    {
        $updated = DB::transaction(function () use ($room) {
            $lockedRoom = Room::lockForUpdate()->findOrFail($room->id);

            if ($lockedRoom->status !== 'cleaning') {
                throw new ConflictHttpException('Hanya kamar berstatus perlu dibersihkan yang dapat ditandai selesai.');
            }

            $lockedRoom->update(['status' => 'available']);

            return $lockedRoom;
        });

        Cache::forget('dashboard_stats');

        return $updated->load('roomType');
    }

    public function updateStatus(Room $room, string $status): Room
    {
        $room->update(['status' => $status]);
        Cache::forget('dashboard_stats');

        return $room->load('roomType');
    }
}
