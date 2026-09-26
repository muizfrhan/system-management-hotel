<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PaymentService
{
    public function recordPayment(array $data): Payment
    {
        try {
            $payment = DB::transaction(function () use ($data) {
                $existing = Payment::where('idempotency_key', $data['idempotency_key'])
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    if (! $this->matchesPaymentRequest($existing, $data)) {
                        throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data pembayaran yang berbeda.');
                    }

                    return $existing;
                }

                $reservation = Reservation::lockForUpdate()->findOrFail($data['reservation_id']);

                if (in_array($reservation->status, ['cancelled', 'checked_out'], true)) {
                    throw new ConflictHttpException('Pembayaran tidak dapat dicatat untuk reservasi yang sudah selesai atau dibatalkan.');
                }

                $chargesTotal = (float) $reservation->charges()->sum('total_price');
                $grandTotal = (float) $reservation->total_price + $chargesTotal;
                $paidTotal = (float) $reservation->payments()
                    ->where('status', 'paid')
                    ->sum('amount');
                $amount = round((float) $data['amount'], 2);

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Jumlah pembayaran harus lebih besar dari nol.',
                    ]);
                }

                if ($amount > max(0, $grandTotal - $paidTotal)) {
                    throw ValidationException::withMessages([
                        'amount' => 'Jumlah pembayaran melebihi sisa tagihan.',
                    ]);
                }

                return Payment::create([
                    'reservation_id' => $reservation->id,
                    'amount' => $amount,
                    'payment_method' => $data['payment_method'],
                    'status' => 'paid',
                    'paid_at' => now(),
                    'idempotency_key' => $data['idempotency_key'],
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isDuplicateKey($exception)) {
                throw $exception;
            }

            $payment = Payment::where('idempotency_key', $data['idempotency_key'])->firstOrFail();
            if (! $this->matchesPaymentRequest($payment, $data)) {
                throw new ConflictHttpException('Idempotency-Key sudah digunakan untuk data pembayaran yang berbeda.');
            }
        }

        if ($payment->wasRecentlyCreated) {
            Cache::forget('dashboard_stats');
        }

        return $payment->load('reservation.guest');
    }

    protected function matchesPaymentRequest(Payment $payment, array $data): bool
    {
        return (int) $payment->reservation_id === (int) $data['reservation_id']
            && (float) $payment->amount === round((float) $data['amount'], 2)
            && $payment->payment_method === $data['payment_method'];
    }

    protected function isDuplicateKey(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && str_contains(strtolower($exception->getMessage()), 'idempotency');
    }

    public function getInvoiceData(Payment $payment): Payment
    {
        return $payment->load(['reservation.guest', 'reservation.room.roomType', 'reservation.charges', 'reservation.payments']);
    }

    public function getInvoiceTotals(Payment $payment): array
    {
        $payment->load(['reservation.guest', 'reservation.room.roomType', 'reservation.charges', 'reservation.payments']);
        $reservation = $payment->reservation;
        $chargesTotal = (float) $reservation->charges->sum('total_price');
        $grandTotal = (float) $reservation->total_price + $chargesTotal;
        $paidTotal = (float) $reservation->payments
            ->where('status', 'paid')
            ->sum('amount');
        $remaining = max(0, $grandTotal - $paidTotal);

        return [
            'room_total' => (float) $reservation->total_price,
            'charges_total' => $chargesTotal,
            'grand_total' => $grandTotal,
            'paid_total' => $paidTotal,
            'remaining' => $remaining,
            'settlement_status' => $remaining <= 0 ? 'paid' : 'unpaid',
            'setting' => Setting::first(),
        ];
    }
}
