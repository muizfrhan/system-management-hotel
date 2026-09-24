<?php

namespace App\Services;

use App\Mail\BookingStatusMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class ReservationService
{
    public function createManualReservation(array $data): Reservation
    {
        $room = Room::with('roomType')->findOrFail($data['room_id']);

        if (!$this->isRoomAvailable($room->id, $data['check_in_date'], $data['check_out_date'])) {
            throw new \Exception('Kamar sudah dibooking pada rentang tanggal tersebut.');
        }

        $nights = Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);
        $totalPrice = $room->roomType->base_price * $nights;

        $guest = Guest::firstOrCreate(
            ['email' => $data['guest_email'] ?? null],
            [
                'name' => $data['guest_name'],
                'phone' => $data['guest_phone'] ?? null,
            ]
        );

        if (!$guest->wasRecentlyCreated) {
            $guest->update([
                'name' => $data['guest_name'],
                'phone' => $data['guest_phone'] ?? $guest->phone,
            ]);
        }

        $reservation = Reservation::create([
            'reservation_code' => Reservation::generateCode(),
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'number_of_guests' => $data['number_of_guests'],
            'status' => 'confirmed',
            'total_price' => $totalPrice,
            'special_requests' => $data['special_requests'] ?? null,
        ]);

        if ($room->status === 'available') {
            $room->update(['status' => 'reserved']);
        }

        if ($guest->email) {
            $this->sendStatusEmail($reservation, 'confirmed');
        }

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }

    public function createOnlineBooking(array $data): Reservation
    {
        $roomType = RoomType::findOrFail($data['room_type_id']);

        $room = Room::where('room_type_id', $roomType->id)
            ->where('status', '!=', 'maintenance')
            ->whereDoesntHave('reservations', function ($q) use ($data) {
                $q->whereNotIn('status', ['cancelled'])
                    ->where('check_in_date', '<', $data['check_out_date'])
                    ->where('check_out_date', '>', $data['check_in_date']);
            })
            ->orderByRaw("FIELD(status, 'available', 'cleaning', 'reserved')")
            ->first();

        if (!$room) {
            throw new \Exception('Maaf, tidak ada kamar tersedia untuk tipe ini pada tanggal yang dipilih.');
        }

        $nights = Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);
        $totalPrice = $roomType->base_price * $nights;

        $guest = Guest::firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'phone' => $data['phone'],
            ]
        );

        $reservation = Reservation::create([
            'reservation_code' => Reservation::generateCode(),
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'number_of_guests' => $data['number_of_guests'],
            'status' => 'pending',
            'total_price' => $totalPrice,
            'special_requests' => $data['special_requests'] ?? null,
        ]);

        if ($room->status === 'available') {
            $room->update(['status' => 'reserved']);
        }

        $this->sendStatusEmail($reservation, 'received');

        return $reservation;
    }

    public function updateReservation(Reservation $reservation, array $data): Reservation
    {
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            throw new \Exception('Hanya reservasi berstatus menunggu/dikonfirmasi yang dapat diubah.');
        }

        $room = Room::with('roomType')->findOrFail($data['room_id']);

        if (!$this->isRoomAvailable($room->id, $data['check_in_date'], $data['check_out_date'], $reservation->id)) {
            throw new \Exception('Kamar sudah dibooking pada rentang tanggal tersebut.');
        }

        $oldRoom = $reservation->room;
        $nights = Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);
        $totalPrice = $room->roomType->base_price * $nights;

        $reservation->update([
            'room_id' => $room->id,
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'number_of_guests' => $data['number_of_guests'],
            'total_price' => $totalPrice,
            'special_requests' => $data['special_requests'] ?? $reservation->special_requests,
        ]);

        if ($oldRoom->id !== $room->id) {
            $this->releaseRoom($oldRoom, $reservation->id);
            if ($room->status === 'available') {
                $room->update(['status' => 'reserved']);
            }
        }

        return $reservation->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function cancelReservation(Reservation $reservation): Reservation
    {
        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            throw new \Exception('Hanya reservasi berstatus menunggu/dikonfirmasi yang dapat dibatalkan.');
        }

        $room = $reservation->room;

        $reservation->update(['status' => 'cancelled']);
        $this->releaseRoom($room, $reservation->id);

        $this->sendStatusEmail($reservation, 'cancelled');

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }

    public function confirmBooking(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => 'confirmed']);
        $this->sendStatusEmail($reservation, 'confirmed');

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }

    public function rejectBooking(Reservation $reservation): Reservation
    {
        $room = $reservation->room;

        $reservation->update(['status' => 'cancelled']);
        $this->releaseRoom($room, $reservation->id);

        $this->sendStatusEmail($reservation, 'rejected');

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }

    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $room = Room::find($roomId);

        if (!$room || $room->status === 'maintenance') {
            return false;
        }

        $conflict = Reservation::where('room_id', $roomId)
            ->when($excludeReservationId, fn ($q) => $q->where('id', '!=', $excludeReservationId))
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->exists();

        return !$conflict;
    }

    public function countAvailableRooms(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return Room::where('room_type_id', $roomTypeId)
            ->where('status', '!=', 'maintenance')
            ->whereDoesntHave('reservations', function ($q) use ($checkIn, $checkOut) {
                $q->whereNotIn('status', ['cancelled'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->count();
    }

    protected function releaseRoom(Room $room, int $exceptReservationId): void
    {
        if ($room->status !== 'reserved') {
            return;
        }

        $hasOtherActive = $room->reservations()
            ->where('id', '!=', $exceptReservationId)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->exists();

        if (!$hasOtherActive) {
            $room->update(['status' => 'available']);
        }
    }

    protected function sendStatusEmail(Reservation $reservation, string $action): void
    {
        if (!$reservation->guest || !$reservation->guest->email) {
            return;
        }

        try {
            Mail::to($reservation->guest->email)->send(new BookingStatusMail($reservation, $action));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Gagal mengirim email booking: ' . $e->getMessage());
        }
    }
}
