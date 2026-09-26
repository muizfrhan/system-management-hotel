<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $role = $request->user()?->role;
        $notifications = [];

        if ($role !== 'housekeeper') {
            $pendingBookings = Reservation::where('status', 'pending')->count();
            if ($pendingBookings > 0) {
                $notifications[] = [
                    'id' => 'booking_pending',
                    'title' => 'Booking Online Baru',
                    'message' => "Ada {$pendingBookings} booking yang menunggu konfirmasi Anda.",
                    'type' => 'warning',
                    'link' => '/booking-online',
                    'time' => 'Sekarang',
                ];
            }

            $todayCheckIn = Reservation::where('status', 'confirmed')
                ->whereDate('check_in_date', today())
                ->count();
            if ($todayCheckIn > 0) {
                $notifications[] = [
                    'id' => 'check_in_today',
                    'title' => 'Check-In Hari Ini',
                    'message' => "Ada {$todayCheckIn} tamu yang dijadwalkan Check-In hari ini.",
                    'type' => 'info',
                    'link' => '/check-in-out',
                    'time' => 'Hari Ini',
                ];
            }

            $todayCheckOut = Reservation::where('status', 'checked_in')
                ->whereDate('check_out_date', today())
                ->count();
            if ($todayCheckOut > 0) {
                $notifications[] = [
                    'id' => 'check_out_today',
                    'title' => 'Check-Out Hari Ini',
                    'message' => "Ada {$todayCheckOut} tamu yang harus Check-Out hari ini.",
                    'type' => 'info',
                    'link' => '/check-in-out',
                    'time' => 'Hari Ini',
                ];
            }
        }

        $dirtyRooms = Room::where('status', 'cleaning')->count();
        if ($dirtyRooms > 0) {
            $notifications[] = [
                'id' => 'dirty_rooms',
                'title' => 'Kamar Belum Dibersihkan',
                'message' => "Ada {$dirtyRooms} kamar dalam status pembersihan oleh Tata Graha.",
                'type' => 'error',
                'link' => $role === 'housekeeper' ? '/housekeeping' : '/rooms',
                'time' => 'Sekarang',
            ];
        }

        return response()->json($notifications);
    }
}
