<?php

namespace App\Services;

use App\Mail\BookingStatusMail;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReservationService
{
    public function createManualReservation(array $data): Reservation
    {
        try {
            $reservation = DB::transaction(function () use ($data) {
                $existing = Reservation::with(['guest', 'room.roomType'])
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if (! $this->matchesManualRequest($existing, $data)) {
                        throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data reservasi yang berbeda.');
                    }

                    return $existing;
                }

                $room = $this->lockRoom((int) $data['room_id']);

                if (in_array($room->status, ['maintenance', 'cleaning', 'occupied'], true)) {
                    throw new ConflictHttpException('Kamar sedang tidak tersedia.');
                }

                $this->assertCapacity($room->roomType, (int) $data['number_of_guests']);

                if (! $this->isRoomAvailable($room->id, $data['check_in_date'], $data['check_out_date'])) {
                    throw new ConflictHttpException('Kamar sudah dibooking pada rentang tanggal tersebut.');
                }

                $guestEmail = $data['guest_email'] ?? null;
                $guest = $guestEmail
                    ? Guest::where('email', $guestEmail)->lockForUpdate()->first()
                    : null;

                if ($guest && ($guest->name !== $data['guest_name'] || ($data['guest_phone'] ?? null) !== $guest->phone)) {
                    $guest = null;
                }

                if (! $guest) {
                    $guest = Guest::create([
                        'name' => $data['guest_name'],
                        'email' => $guestEmail,
                        'phone' => $data['guest_phone'] ?? null,
                    ]);
                }

                $nights = (int) Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);
                $totalPrice = $this->calculateTotal($room->roomType->base_price, $nights);

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
                    'idempotency_key' => $data['idempotency_key'],
                ]);

                if ($room->status === 'available') {
                    $room->update(['status' => 'reserved']);
                }

                return $reservation;
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKey($exception)) {
                throw $exception;
            }

            $reservation = Reservation::with(['guest', 'room.roomType'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->firstOrFail();

            if (! $this->matchesManualRequest($reservation, $data)) {
                throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data reservasi yang berbeda.');
            }
        }

        if ($reservation->wasRecentlyCreated) {
            Cache::forget('dashboard_stats');
            $this->sendStatusEmail($reservation, 'confirmed');
        }

        return $reservation->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function createOnlineBooking(array $data): Reservation
    {
        try {
            $reservation = DB::transaction(function () use ($data) {
                $existing = Reservation::with(['guest', 'room.roomType'])
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if (! $this->matchesOnlineRequest($existing, $data)) {
                        throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data pemesanan yang berbeda.');
                    }

                    return $existing;
                }

                $roomType = RoomType::where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($data['room_type_id']);

                $this->assertCapacity($roomType, (int) $data['number_of_guests']);

                $room = Room::where('room_type_id', $roomType->id)
                    ->whereIn('status', ['available', 'reserved'])
                    ->whereDoesntHave('reservations', function ($query) use ($data) {
                        $query->whereNotIn('status', ['cancelled', 'checked_out'])
                            ->where('check_in_date', '<', $data['check_out_date'])
                            ->where('check_out_date', '>', $data['check_in_date']);
                    })
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if (! $room) {
                    throw new ConflictHttpException('Maaf, tidak ada kamar tersedia untuk tipe ini pada tanggal yang dipilih.');
                }

                $guest = Guest::where('email', $data['email'])
                    ->where('name', $data['name'])
                    ->where('phone', $data['phone'])
                    ->lockForUpdate()
                    ->first();

                if (! $guest) {
                    $guest = Guest::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                    ]);
                }

                $nights = (int) Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);

                $reservation = Reservation::create([
                    'reservation_code' => Reservation::generateCode(),
                    'guest_id' => $guest->id,
                    'room_id' => $room->id,
                    'check_in_date' => $data['check_in_date'],
                    'check_out_date' => $data['check_out_date'],
                    'number_of_guests' => $data['number_of_guests'],
                    'status' => 'pending',
                    'total_price' => $this->calculateTotal($roomType->base_price, $nights),
                    'special_requests' => $data['special_requests'] ?? null,
                    'idempotency_key' => $data['idempotency_key'],
                ]);

                $room->update(['status' => 'reserved']);

                return $reservation;
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKey($exception)) {
                throw $exception;
            }

            $reservation = Reservation::with(['guest', 'room.roomType'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->firstOrFail();

            if (! $this->matchesOnlineRequest($reservation, $data)) {
                throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data pemesanan yang berbeda.');
            }
        }

        if ($reservation->wasRecentlyCreated) {
            Cache::forget('dashboard_stats');
            $this->sendStatusEmail($reservation, 'received');
        }

        return $reservation->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function updateReservation(Reservation $reservation, array $data): Reservation
    {
        $updated = DB::transaction(function () use ($reservation, $data) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($lockedReservation->status, ['pending', 'confirmed'], true)) {
                throw new ConflictHttpException('Hanya reservasi berstatus menunggu/dikonfirmasi yang dapat diubah.');
            }

            if ($lockedReservation->payments()->where('status', 'paid')->exists()) {
                throw new ConflictHttpException('Reservasi yang sudah memiliki pembayaran tidak dapat diubah.');
            }

            $roomIds = collect([$lockedReservation->room_id, (int) $data['room_id']])
                ->unique()
                ->sort()
                ->values();

            $rooms = Room::with('roomType')
                ->whereIn('id', $roomIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $room = $rooms->get((int) $data['room_id']);

            if (! $room || $room->status === 'maintenance') {
                throw new ConflictHttpException('Kamar tidak tersedia.');
            }

            $this->assertCapacity($room->roomType, (int) $data['number_of_guests']);

            if (! $this->isRoomAvailable($room->id, $data['check_in_date'], $data['check_out_date'], $lockedReservation->id)) {
                throw new ConflictHttpException('Kamar sudah dibooking pada rentang tanggal tersebut.');
            }

            $oldRoom = $rooms->get($lockedReservation->room_id);
            $nights = (int) Carbon::parse($data['check_in_date'])->diffInDays($data['check_out_date']);

            $lockedReservation->update([
                'room_id' => $room->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'number_of_guests' => $data['number_of_guests'],
                'total_price' => $this->calculateTotal($room->roomType->base_price, $nights),
                'special_requests' => $data['special_requests'] ?? $lockedReservation->special_requests,
            ]);

            if ($oldRoom->id !== $room->id) {
                $this->releaseRoom($oldRoom, $lockedReservation->id);

                if ($room->status === 'available') {
                    $room->update(['status' => 'reserved']);
                }
            }

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');

        return $updated->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function cancelReservation(Reservation $reservation): Reservation
    {
        $cancelled = DB::transaction(function () use ($reservation) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($lockedReservation->status, ['pending', 'confirmed'], true)) {
                throw new ConflictHttpException('Hanya reservasi berstatus menunggu/dikonfirmasi yang dapat dibatalkan.');
            }

            if ($lockedReservation->payments()->where('status', 'paid')->exists()) {
                throw new ConflictHttpException('Pembayaran harus diselesaikan atau dikembalikan sebelum pembatalan.');
            }

            $room = $this->lockRoom($lockedReservation->room_id);
            $lockedReservation->update(['status' => 'cancelled']);
            $this->releaseRoom($room, $lockedReservation->id);

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');
        $this->sendStatusEmail($cancelled, 'cancelled');

        return $cancelled->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function confirmBooking(Reservation $reservation): Reservation
    {
        $confirmed = DB::transaction(function () use ($reservation) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== 'pending') {
                throw new ConflictHttpException('Hanya reservasi menunggu yang dapat dikonfirmasi.');
            }

            $room = $this->lockRoom($lockedReservation->room_id);

            if (! in_array($room->status, ['available', 'reserved'], true)) {
                throw new ConflictHttpException('Kamar tidak dapat dikonfirmasi karena statusnya tidak tersedia.');
            }

            if ($room->status === 'available') {
                $room->update(['status' => 'reserved']);
            }

            $lockedReservation->update(['status' => 'confirmed']);

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');
        $this->sendStatusEmail($confirmed, 'confirmed');

        return $confirmed->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function rejectBooking(Reservation $reservation): Reservation
    {
        $rejected = DB::transaction(function () use ($reservation) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== 'pending') {
                throw new ConflictHttpException('Hanya reservasi menunggu yang dapat ditolak.');
            }

            if ($lockedReservation->payments()->where('status', 'paid')->exists()) {
                throw new ConflictHttpException('Pembayaran harus diselesaikan sebelum booking ditolak.');
            }

            $room = $this->lockRoom($lockedReservation->room_id);
            $lockedReservation->update(['status' => 'cancelled']);
            $this->releaseRoom($room, $lockedReservation->id);

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');
        $this->sendStatusEmail($rejected, 'rejected');

        return $rejected->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $excludeReservationId = null): bool
    {
        $room = Room::find($roomId);

        if (! $room || in_array($room->status, ['maintenance', 'cleaning', 'occupied'], true)) {
            return false;
        }

        return ! Reservation::where('room_id', $roomId)
            ->when($excludeReservationId, fn ($query) => $query->where('id', '!=', $excludeReservationId))
            ->whereNotIn('status', ['cancelled', 'checked_out'])
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->exists();
    }

    public function countAvailableRooms(int $roomTypeId, string $checkIn, string $checkOut): int
    {
        return Room::where('room_type_id', $roomTypeId)
            ->whereIn('status', ['available', 'reserved'])
            ->whereDoesntHave('reservations', function ($query) use ($checkIn, $checkOut) {
                $query->whereNotIn('status', ['cancelled', 'checked_out'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->count();
    }

    protected function lockRoom(int $roomId): Room
    {
        return Room::with('roomType')->lockForUpdate()->findOrFail($roomId);
    }

    protected function assertCapacity(RoomType $roomType, int $numberOfGuests): void
    {
        if ($numberOfGuests > $roomType->capacity) {
            throw ValidationException::withMessages([
                'number_of_guests' => "Jumlah tamu melebihi kapasitas kamar (maksimal {$roomType->capacity} tamu).",
            ]);
        }
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

        if (! $hasOtherActive) {
            $room->update(['status' => 'available']);
        }
    }

    protected function calculateTotal(float|int|string $price, int $nights): float
    {
        if ($nights < 1 || $nights > 365) {
            throw ValidationException::withMessages([
                'check_out_date' => 'Durasi pemesanan harus antara 1 hingga 365 malam.',
            ]);
        }

        $total = round((float) $price * $nights, 2);
        if ($total > 9999999999.99) {
            throw ValidationException::withMessages([
                'room_type_id' => 'Total harga pemesanan melebihi batas yang diperbolehkan.',
            ]);
        }

        return $total;
    }

    protected function matchesManualRequest(Reservation $reservation, array $data): bool
    {
        return (int) $reservation->room_id === (int) $data['room_id']
            && $reservation->check_in_date->toDateString() === Carbon::parse($data['check_in_date'])->toDateString()
            && $reservation->check_out_date->toDateString() === Carbon::parse($data['check_out_date'])->toDateString()
            && (int) $reservation->number_of_guests === (int) $data['number_of_guests']
            && $reservation->guest?->name === $data['guest_name']
            && (string) $reservation->guest?->email === (string) ($data['guest_email'] ?? '')
            && (string) $reservation->guest?->phone === (string) ($data['guest_phone'] ?? '')
            && (string) $reservation->special_requests === (string) ($data['special_requests'] ?? '');
    }

    protected function matchesOnlineRequest(Reservation $reservation, array $data): bool
    {
        return (int) $reservation->room?->room_type_id === (int) $data['room_type_id']
            && $reservation->check_in_date->toDateString() === Carbon::parse($data['check_in_date'])->toDateString()
            && $reservation->check_out_date->toDateString() === Carbon::parse($data['check_out_date'])->toDateString()
            && (int) $reservation->number_of_guests === (int) $data['number_of_guests']
            && $reservation->guest?->name === $data['name']
            && $reservation->guest?->email === $data['email']
            && $reservation->guest?->phone === $data['phone']
            && (string) $reservation->special_requests === (string) ($data['special_requests'] ?? '');
    }

    protected function isDuplicateKey(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains(strtolower($exception->getMessage()), 'idempotency');
    }

    protected function sendStatusEmail(Reservation $reservation, string $action): void
    {
        if (! $reservation->guest || ! $reservation->guest->email) {
            return;
        }

        try {
            Mail::to($reservation->guest->email)->send(new BookingStatusMail($reservation, $action));
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengirim email booking.', [
                'reservation_id' => $reservation->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
