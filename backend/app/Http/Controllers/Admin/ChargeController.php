<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ChargeController extends Controller
{
    public function store(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1|max:1000',
            'unit_price' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
        ]);

        $charge = DB::transaction(function () use ($reservation, $validated) {
            $lockedReservation = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (in_array($lockedReservation->status, ['cancelled', 'checked_out'], true)) {
                throw new ConflictHttpException('Tidak dapat menambah biaya pada reservasi ini.');
            }

            if ($lockedReservation->payments()->where('status', 'paid')->exists()) {
                throw new ConflictHttpException('Folio tidak dapat diubah setelah pembayaran tercatat.');
            }

            $totalPrice = round((float) $validated['quantity'] * (float) $validated['unit_price'], 2);
            if ($totalPrice > 9999999999.99) {
                throw ValidationException::withMessages([
                    'unit_price' => 'Total biaya tambahan melebihi batas yang diperbolehkan.',
                ]);
            }

            return Charge::create([
                'reservation_id' => $lockedReservation->id,
                'description' => $validated['description'],
                'category' => $validated['category'],
                'quantity' => $validated['quantity'],
                'unit_price' => $validated['unit_price'],
                'total_price' => $totalPrice,
            ]);
        });

        Cache::forget('dashboard_stats');

        return response()->json($charge, 201);
    }

    public function destroy(Charge $charge): JsonResponse
    {
        DB::transaction(function () use ($charge) {
            $lockedCharge = Charge::lockForUpdate()->findOrFail($charge->id);
            $reservation = Reservation::lockForUpdate()->findOrFail($lockedCharge->reservation_id);

            if (in_array($reservation->status, ['cancelled', 'checked_out'], true)) {
                throw new ConflictHttpException('Tidak dapat menghapus biaya pada reservasi ini.');
            }

            if ($reservation->payments()->where('status', 'paid')->exists()) {
                throw new ConflictHttpException('Folio tidak dapat diubah setelah pembayaran tercatat.');
            }

            $lockedCharge->delete();
        });

        Cache::forget('dashboard_stats');

        return response()->json(['message' => 'Biaya tambahan dihapus.']);
    }
}
