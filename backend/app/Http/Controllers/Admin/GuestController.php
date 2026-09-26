<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
        ]);

        $query = Guest::query()->withCount('reservations');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $guests = $query->latest()->paginate(10);

        return response()->json($guests);
    }

    public function show(Guest $guest): JsonResponse
    {
        $reservations = $guest->reservations()
            ->with('room.roomType')
            ->latest()
            ->limit(100)
            ->get();

        $guest->setRelation('reservations', $reservations);

        return response()->json($guest);
    }
}
