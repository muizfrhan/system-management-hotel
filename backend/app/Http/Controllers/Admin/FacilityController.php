<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FacilityController extends Controller
{
    public function index(): JsonResponse
    {
        $facilities = Cache::remember('facilities_list', 86400, function () {
            return Facility::all();
        });
        return response()->json($facilities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility = Facility::create($validated);
        Cache::forget('facilities_list');
        return response()->json($facility, 201);
    }

    public function update(Request $request, Facility $facility): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:255',
        ]);

        $facility->update($validated);
        Cache::forget('facilities_list');
        return response()->json($facility);
    }

    public function destroy(Facility $facility): JsonResponse
    {
        $facility->delete();
        Cache::forget('facilities_list');
        return response()->json(['message' => 'Fasilitas berhasil dihapus.']);
    }
}
