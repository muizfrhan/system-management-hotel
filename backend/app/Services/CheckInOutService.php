<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;

class CheckInOutService
{
    public function processCheckIn(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => 'checked_in']);
        $reservation->room->update(['status' => 'occupied']);

        \Illuminate\Support\Facades\Cache::forget('dashboard_stats');

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }

    public function processCheckOut(Reservation $reservation): Reservation
    {
        $reservation->update(['status' => 'checked_out']);
        $reservation->room->update(['status' => 'cleaning']);

        $chargesTotal = (float) $reservation->charges()->sum('total_price');
        $folioTotal = (float) $reservation->total_price + $chargesTotal;

        if (!Payment::where('reservation_id', $reservation->id)->exists()) {
            Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $folioTotal,
                'payment_method' => 'Sistem Otomatis (Cash)',
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        \Illuminate\Support\Facades\Cache::forget('dashboard_stats');

        return $reservation->load(['guest', 'room.roomType', 'charges']);
    }
}
