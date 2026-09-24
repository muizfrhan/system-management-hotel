<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class RoomTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $roomTypes = Cache::remember('room_types_list', 3600, function () {
            return RoomType::with(['facilities', 'rooms'])->get();
        });
        return response()->json($roomTypes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'capacity' => 'required|integer|min:1',
            'size' => 'nullable|string|max:50',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp,avif|max:2048',
            'custom_facilities' => 'nullable|string',
        ]);

        if (isset($validated['custom_facilities'])) {
            $validated['custom_facilities'] = json_decode($validated['custom_facilities'], true);
        }

        $imagePaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $imagePaths[] = $photo->store('room-types', 'public');
            }
        }
        $validated['images'] = $imagePaths;

        unset($validated['photos']);

        $roomType = RoomType::create($validated);

        $facilities = $request->input('facilities', []);
        $roomType->facilities()->sync($facilities);

        Cache::forget('room_types_list');

        return response()->json($roomType->load('facilities'), 201);
    }

    public function show(RoomType $roomType): JsonResponse
    {
        return response()->json($roomType->load(['facilities', 'rooms']));
    }

    public function update(Request $request, RoomType $roomType): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'base_price' => 'sometimes|numeric|min:0',
            'capacity' => 'sometimes|integer|min:1',
            'size' => 'nullable|string|max:50',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp,avif|max:2048',
            'custom_facilities' => 'nullable|string',
        ]);

        if (isset($validated['custom_facilities'])) {
            $validated['custom_facilities'] = json_decode($validated['custom_facilities'], true);
        }

        if ($request->hasFile('photos')) {
            $oldImages = $roomType->images ?? [];
            foreach ($oldImages as $old) {
                Storage::disk('public')->delete($old);
            }
            $imagePaths = [];
            foreach ($request->file('photos') as $photo) {
                $imagePaths[] = $photo->store('room-types', 'public');
            }
            $validated['images'] = $imagePaths;
        }

        $keepImages = $request->input('keep_images');
        if ($keepImages !== null) {
            $keep = is_string($keepImages) ? json_decode($keepImages, true) : $keepImages;
            if (is_array($keep)) {
                $oldImages = $roomType->images ?? [];
                foreach ($oldImages as $old) {
                    if (!in_array($old, $keep)) {
                        Storage::disk('public')->delete($old);
                    }
                }
                $newUploads = $validated['images'] ?? [];
                $validated['images'] = array_merge($keep, $newUploads);
            }
        }

        unset($validated['photos']);

        $roomType->update($validated);

        $facilities = $request->input('facilities', []);
        $roomType->facilities()->sync($facilities);

        Cache::forget('room_types_list');

        return response()->json($roomType->load('facilities'));
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        $oldImages = $roomType->images ?? [];
        foreach ($oldImages as $old) {
            Storage::disk('public')->delete($old);
        }
        if ($roomType->image) {
            Storage::disk('public')->delete($roomType->image);
        }
        $roomType->delete();

        Cache::forget('room_types_list');

        return response()->json(['message' => 'Tipe kamar berhasil dihapus.']);
    }
}
