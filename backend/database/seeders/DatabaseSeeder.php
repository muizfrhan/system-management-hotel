<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@lokanata.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Resepsionis',
            'email' => 'resepsionis@lokanata.com',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
        ]);

        User::create([
            'name' => 'Tata Graha',
            'email' => 'housekeeping@lokanata.com',
            'password' => Hash::make('password'),
            'role' => 'housekeeper',
        ]);

        Setting::create([
            'hotel_name' => 'Lokanata Hotel',
            'tagline' => 'Pengalaman Menginap Terbaik',
            'address' => 'Jl. Raya Lokanata No. 1, Jakarta',
            'phone' => '021-12345678',
            'email' => 'info@lokanata.com',
        ]);

        $facilities = ['WiFi', 'AC', 'TV', 'Kolam Renang', 'Parkir', 'Restoran', 'Gym', 'Laundry'];
        foreach ($facilities as $name) {
            Facility::create(['name' => $name, 'icon' => strtolower(str_replace(' ', '-', $name))]);
        }

        $deluxe = RoomType::create([
            'name' => 'Deluxe',
            'base_price' => 500000,
            'capacity' => 2,
            'size' => '32 m²',
            'bed_type' => 'King',
            'description' => 'Kamar luas dengan pemandangan kota yang indah.',
            'is_active' => true,
        ]);
        $deluxe->facilities()->attach(Facility::whereIn('name', ['WiFi', 'AC', 'TV'])->pluck('id'));

        $superior = RoomType::create([
            'name' => 'Superior',
            'base_price' => 350000,
            'capacity' => 2,
            'size' => '28 m²',
            'bed_type' => 'Queen',
            'description' => 'Kamar nyaman dengan fasilitas lengkap.',
            'is_active' => true,
        ]);
        $superior->facilities()->attach(Facility::whereIn('name', ['WiFi', 'AC', 'TV'])->pluck('id'));

        $standard = RoomType::create([
            'name' => 'Standard',
            'base_price' => 250000,
            'capacity' => 2,
            'size' => '24 m²',
            'bed_type' => 'Double',
            'description' => 'Kamar standar yang bersih dan rapi.',
            'is_active' => true,
        ]);
        $standard->facilities()->attach(Facility::whereIn('name', ['WiFi', 'AC'])->pluck('id'));

        $floors = ['1', '2', '3'];
        $roomNumber = 101;
        foreach ([$deluxe, $superior, $standard] as $type) {
            foreach ($floors as $floor) {
                for ($i = 0; $i < 2; $i++) {
                    Room::create([
                        'room_type_id' => $type->id,
                        'room_number' => (string)$roomNumber,
                        'floor' => $floor,
                        'status' => 'available',
                    ]);
                    $roomNumber++;
                }
            }
        }
    }
}
