<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

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
            'base_price' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'capacity' => 'required|integer|min:1|max:20',
            'size' => 'nullable|string|max:50',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'sometimes|boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp,avif|max:2048',
            'custom_facilities' => 'nullable|string|max:10000',
        ]);

        if (array_key_exists('custom_facilities', $validated)) {
            $validated['custom_facilities'] = $this->decodeCustomFacilities($validated['custom_facilities']);
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

        if (array_key_exists('facilities', $validated)) {
            $roomType->facilities()->sync($validated['facilities'] ?? []);
        }

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
            'base_price' => 'sometimes|numeric|decimal:0,2|min:0|max:99999999.99',
            'capacity' => 'sometimes|integer|min:1|max:20',
            'size' => 'nullable|string|max:50',
            'bed_type' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:5000',
            'is_active' => 'sometimes|boolean',
            'facilities' => 'nullable|array',
            'facilities.*' => 'exists:facilities,id',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'file|mimes:jpg,jpeg,png,gif,webp,avif|max:2048',
            'custom_facilities' => 'nullable|string|max:10000',
        ]);

        if (isset($validated['capacity']) && (int) $validated['capacity'] < $roomType->capacity) {
            DB::transaction(function () use ($roomType, $validated) {
                $lockedRoomType = RoomType::lockForUpdate()->findOrFail($roomType->id);
                $this->assertCapacityCanBeUpdated($lockedRoomType, (int) $validated['capacity']);
            });
        }

        if (array_key_exists('custom_facilities', $validated)) {
            $validated['custom_facilities'] = $this->decodeCustomFacilities($validated['custom_facilities']);
        }

        $hasNewPhotos = $request->hasFile('photos');
        $oldImages = $roomType->images ?? [];
        $keepImages = $request->input('keep_images');
        $keep = null;

        if ($keepImages !== null) {
            $keep = is_string($keepImages) ? json_decode($keepImages, true) : $keepImages;

            if (! is_array($keep) || count($keep) > 3) {
                throw ValidationException::withMessages([
                    'keep_images' => 'Daftar gambar yang dipertahankan tidak valid.',
                ]);
            }

            foreach ($keep as $path) {
                if (! is_string($path) || ! in_array($path, $oldImages, true)) {
                    throw ValidationException::withMessages([
                        'keep_images' => 'Daftar gambar tidak valid.',
                    ]);
                }
            }

            $newPhotos = $request->file('photos', []);
            $newPhotoCount = is_array($newPhotos) ? count($newPhotos) : ($newPhotos ? 1 : 0);
            if (count($keep) + $newPhotoCount > 3) {
                throw ValidationException::withMessages([
                    'photos' => 'Maksimal tiga gambar per tipe kamar.',
                ]);
            }
        }

        if ($hasNewPhotos) {
            $imagePaths = [];
            foreach ($request->file('photos') as $photo) {
                $imagePaths[] = $photo->store('room-types', 'public');
            }
            $validated['images'] = $imagePaths;
        }

        if ($keep !== null) {
            foreach ($oldImages as $old) {
                if (! in_array($old, $keep, true)) {
                    Storage::disk('public')->delete($old);
                }
            }

            $validated['images'] = array_merge($keep, $validated['images'] ?? []);
        } elseif ($hasNewPhotos) {
            foreach ($oldImages as $old) {
                Storage::disk('public')->delete($old);
            }
        }

        unset($validated['photos']);

        $updated = DB::transaction(function () use ($roomType, $validated) {
            $lockedRoomType = RoomType::lockForUpdate()->findOrFail($roomType->id);

            if (isset($validated['capacity']) && (int) $validated['capacity'] < $lockedRoomType->capacity) {
                $this->assertCapacityCanBeUpdated($lockedRoomType, (int) $validated['capacity']);
            }

            $lockedRoomType->update($validated);

            if (array_key_exists('facilities', $validated)) {
                $lockedRoomType->facilities()->sync($validated['facilities'] ?? []);
            }

            return $lockedRoomType;
        });

        Cache::forget('room_types_list');

        return response()->json($updated->load('facilities'));
    }

    public function destroy(RoomType $roomType): JsonResponse
    {
        DB::transaction(function () use ($roomType) {
            $lockedRoomType = RoomType::lockForUpdate()->findOrFail($roomType->id);

            if ($lockedRoomType->rooms()->exists()) {
                throw new ConflictHttpException('Tipe kamar yang memiliki kamar terdaftar tidak dapat dihapus.');
            }

            $oldImages = $lockedRoomType->images ?? [];
            foreach ($oldImages as $old) {
                Storage::disk('public')->delete($old);
            }
            if ($lockedRoomType->image) {
                Storage::disk('public')->delete($lockedRoomType->image);
            }
            $lockedRoomType->delete();
        });

        Cache::forget('room_types_list');
        Cache::forget('dashboard_stats');

        return response()->json(['message' => 'Tipe kamar berhasil dihapus.']);
    }

    protected function assertCapacityCanBeUpdated(RoomType $roomType, int $capacity): void
    {
        $hasConflict = $roomType->rooms()
            ->whereHas('reservations', function ($query) use ($capacity) {
                $query->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                    ->whereDate('check_out_date', '>=', today())
                    ->where('number_of_guests', '>', $capacity);
            })
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'capacity' => 'Kapasitas baru lebih kecil dari reservasi aktif yang sudah ada.',
            ]);
        }
    }

    protected function decodeCustomFacilities(?string $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw ValidationException::withMessages([
                'custom_facilities' => 'Format fasilitas khusus tidak valid.',
            ]);
        }

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'custom_facilities' => 'Fasilitas khusus harus berupa daftar JSON.',
            ]);
        }

        foreach ($decoded as $value) {
            if (! is_string($value)) {
                throw ValidationException::withMessages([
                    'custom_facilities' => 'Setiap fasilitas khusus harus berupa teks.',
                ]);
            }
        }

        return $decoded;
    }
}
