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
        $this->seedDemoUsers();
        $this->seedHotelSettings();
        $this->seedFacilities();
        $this->seedRooms();
    }

    protected function seedDemoUsers(): void
    {
        $password = env('DEMO_USER_PASSWORD');

        if (! $password || ! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = [
            ['name' => 'Administrator', 'email' => 'admin@lokanata.com', 'role' => 'admin'],
            ['name' => 'Resepsionis', 'email' => 'resepsionis@lokanata.com', 'role' => 'receptionist'],
            ['name' => 'Tata Graha', 'email' => 'housekeeping@lokanata.com', 'role' => 'housekeeper'],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make($password),
                ]
            );
        }
    }

    protected function seedHotelSettings(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'hotel_name' => 'Lokanata Hotel',
                'tagline' => 'Pengalaman Menginap Terbaik',
                'address' => 'Jl. Raya Lokanata No. 1, Jakarta',
                'phone' => '021-12345678',
                'email' => 'info@lokanata.com',
            ]
        );
    }

    protected function seedFacilities(): void
    {
        $facilities = ['WiFi', 'AC', 'TV', 'Kolam Renang', 'Parkir', 'Restoran', 'Gym', 'Laundry'];

        foreach ($facilities as $name) {
            Facility::updateOrCreate(
                ['name' => $name],
                ['icon' => strtolower(str_replace(' ', '-', $name))]
            );
        }
    }

    protected function seedRooms(): void
    {
        $roomTypes = [
            [
                'name' => 'Deluxe',
                'base_price' => 500000,
                'capacity' => 2,
                'size' => '32 m²',
                'bed_type' => 'King',
                'description' => 'Kamar luas dengan pemandangan kota yang indah.',
                'facilities' => ['WiFi', 'AC', 'TV'],
            ],
            [
                'name' => 'Superior',
                'base_price' => 350000,
                'capacity' => 2,
                'size' => '28 m²',
                'bed_type' => 'Queen',
                'description' => 'Kamar nyaman dengan fasilitas lengkap.',
                'facilities' => ['WiFi', 'AC', 'TV'],
            ],
            [
                'name' => 'Standard',
                'base_price' => 250000,
                'capacity' => 2,
                'size' => '24 m²',
                'bed_type' => 'Double',
                'description' => 'Kamar standar yang bersih dan rapi.',
                'facilities' => ['WiFi', 'AC'],
            ],
        ];

        $roomNumber = 101;

        foreach ($roomTypes as $data) {
            $facilityNames = $data['facilities'];
            unset($data['facilities']);

            $roomType = RoomType::updateOrCreate(
                ['name' => $data['name']],
                $data + ['is_active' => true]
            );

            $roomType->facilities()->sync(
                Facility::whereIn('name', $facilityNames)->pluck('id')
            );

            foreach (['1', '2', '3'] as $floor) {
                foreach (range(1, 2) as $unused) {
                    Room::firstOrCreate(
                        ['room_number' => (string) $roomNumber],
                        [
                            'room_type_id' => $roomType->id,
                            'floor' => $floor,
                        ]
                    );
                    $roomNumber++;
                }
            }
        }
    }
}
