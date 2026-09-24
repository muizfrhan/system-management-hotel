<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Charge;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChargeController extends Controller
{
    public function store(Request $request, Reservation $reservation): JsonResponse
    {
        if (in_array($reservation->status, ['cancelled', 'checked_out'])) {
            return response()->json(['message' => 'Tidak dapat menambah biaya pada reservasi ini.'], 422);
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        $validated['reservation_id'] = $reservation->id;
        $validated['total_price'] = $validated['quantity'] * $validated['unit_price'];

        $charge = Charge::create($validated);

        return response()->json($charge, 201);
    }

    public function destroy(Charge $charge): JsonResponse
    {
        if (in_array($charge->reservation->status, ['cancelled', 'checked_out'])) {
            return response()->json(['message' => 'Tidak dapat menghapus biaya pada reservasi ini.'], 422);
        }

        $charge->delete();

        return response()->json(['message' => 'Biaya tambahan dihapus.']);
    }
}
