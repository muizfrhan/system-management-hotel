<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use Illuminate\Http\JsonResponse;

class GuestController extends Controller
{
    public function index(): JsonResponse
    {
        $guests = Guest::withCount('reservations')
            ->latest()
            ->paginate(10);

        return response()->json($guests);
    }

    public function show(Guest $guest): JsonResponse
    {
        return response()->json($guest->load('reservations.room.roomType'));
    }
}
