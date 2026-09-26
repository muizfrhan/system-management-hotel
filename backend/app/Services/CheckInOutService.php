<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CheckInOutService
{
    public function processCheckIn(Reservation $reservation): Reservation
    {
        $checkedIn = DB::transaction(function () use ($reservation) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== 'confirmed') {
                throw new ConflictHttpException('Hanya reservasi berstatus terkonfirmasi yang dapat	check-in.');
            }

            if ($lockedReservation->check_in_date->isFuture()) {
                throw new ConflictHttpException('Check-in belum dapat dilakukan sebelum tanggal check-in reservasi.');
            }

            $room = $lockedReservation->room()->lockForUpdate()->firstOrFail();

            if (! in_array($room->status, ['available', 'reserved'], true)) {
                throw new ConflictHttpException('Status kamar tidak memungkinkan proses check-in.');
            }

            $hasOccupiedConflict = Reservation::where('room_id', $room->id)
                ->where('id', '!=', $lockedReservation->id)
                ->where('status', 'checked_in')
                ->where('check_in_date', '<', $lockedReservation->check_out_date)
                ->where('check_out_date', '>', $lockedReservation->check_in_date)
                ->exists();

            if ($hasOccupiedConflict) {
                throw new ConflictHttpException('Kamar sedang digunakan oleh reservasi aktif lain.');
            }

            $lockedReservation->update(['status' => 'checked_in']);
            $room->update(['status' => 'occupied']);

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');

        return $checkedIn->load(['guest', 'room.roomType', 'charges', 'payments']);
    }

    public function processCheckOut(Reservation $reservation): Reservation
    {
        $checkedOut = DB::transaction(function () use ($reservation) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if ($lockedReservation->status !== 'checked_in') {
                throw new ConflictHttpException('Hanya reservasi yang sudah check-in dapat check-out.');
            }

            $chargesTotal = (float) $lockedReservation->charges()->sum('total_price');
            $grandTotal = (float) $lockedReservation->total_price + $chargesTotal;
            $paidTotal = (float) $lockedReservation->payments()->where('status', 'paid')->sum('amount');
            if ($grandTotal > $paidTotal) {
                throw new ConflictHttpException('Sisa tagihan harus dilunasi sebelum check-out.');
            }

            $room = $lockedReservation->room()->lockForUpdate()->firstOrFail();
            $lockedReservation->update(['status' => 'checked_out']);
            $room->update(['status' => 'cleaning']);

            return $lockedReservation;
        });

        Cache::forget('dashboard_stats');

        return $checkedOut->load(['guest', 'room.roomType', 'charges', 'payments']);
    }
}
