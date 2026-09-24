<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
        ]);

        $payment = $this->paymentService->recordPayment($validated);

        return response()->json($payment, 201);
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
        ]);

        $filename = 'invoice-' . $payment->reservation->reservation_code . '.pdf';

        return $pdf->stream($filename);
    }
}
