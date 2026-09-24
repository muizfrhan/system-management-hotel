<?php

namespace App\Services;

use App\Models\Room;

class RoomStatusService
{
    public function markAsAvailable(Room $room): Room
    {
        $room->update(['status' => 'available']);
        return $room->load('roomType');
    }

    public function updateStatus(Room $room, string $status): Room
    {
        $room->update(['status' => $status]);
        return $room->load('roomType');
    }
}
