<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function index(): JsonResponse
    {
        $payments = Payment::with('reservation.guest')
            ->latest()
            ->paginate(10);

        return response()->json($payments);
    }

    public function store(Request $request): JsonResponse
    {
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 100) {
            throw ValidationException::withMessages([
                'idempotency_key' => 'Header Idempotency-Key wajib diisi dan maksimal 100 karakter.',
            ]);
        }

        $validated = $request->validate([
            'reservation_id' => 'required|integer|exists:reservations,id',
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'payment_method' => 'required|string|in:cash,card,transfer,qris',
        ]);
        $validated['idempotency_key'] = $idempotencyKey;

        $payment = $this->paymentService->recordPayment($validated);

        return response()->json($payment, $payment->wasRecentlyCreated ? 201 : 200);
    }

    public function invoice(Payment $payment): JsonResponse
    {
        $invoiceData = $this->paymentService->getInvoiceData($payment);
        $totals = $this->paymentService->getInvoiceTotals($payment);

        return response()->json(array_merge(
            $invoiceData->toArray(),
            ['totals' => $totals]
        ));
    }

    public function invoicePdf(Payment $payment)
    {
        $payment->load(['reservation.guest', 'reservation.room.roomType', 'reservation.charges']);
        $totals = $this->paymentService->getInvoiceTotals($payment);

        $pdf = Pdf::loadView('invoices.invoice', [
            'payment' => $payment,
            'reservation' => $payment->reservation,
            'setting' => $totals['setting'],
            'room_total' => $totals['room_total'],
            'charges_total' => $totals['charges_total'],
            'grand_total' => $totals['grand_total'],
            'paid_total' => $totals['paid_total'],
            'remaining' => $totals['remaining'],
            'settlement_status' => $totals['settlement_status'],
        ]);

        $filename = 'invoice-'.$payment->reservation->reservation_code.'.pdf';

        return $pdf->stream($filename);
    }
}
