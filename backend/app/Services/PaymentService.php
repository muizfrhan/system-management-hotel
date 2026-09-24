<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;

class PaymentService
{
    public function recordPayment(array $data): Payment
    {
        $payment = Payment::create([
            'reservation_id' => $data['reservation_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return $payment->load('reservation.guest');
    }

    public function getInvoiceData(Payment $payment): Payment
    {
        return $payment->load(['reservation.guest', 'reservation.room.roomType', 'reservation.charges']);
    }

    public function getInvoiceTotals(Payment $payment): array
    {
        $payment->load(['reservation.guest', 'reservation.room.roomType', 'reservation.charges']);
        $reservation = $payment->reservation;
        $chargesTotal = (float) $reservation->charges->sum('total_price');

        return [
            'room_total' => (float) $reservation->total_price,
            'charges_total' => $chargesTotal,
            'grand_total' => (float) $reservation->total_price + $chargesTotal,
            'setting' => Setting::first(),
        ];
    }
}
