<?php

use App\Http\Controllers\Admin\BookingOnlineController;
use App\Http\Controllers\Admin\ChargeController;
use App\Http\Controllers\Admin\CheckInOutController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityController;
use App\Http\Controllers\Admin\GuestController;
use App\Http\Controllers\Admin\HousekeepingController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\RoomTypeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Guest\BookingController;
use App\Http\Controllers\Guest\LandingPageController;
use App\Http\Controllers\Guest\TrackBookingController;
use App\Http\Controllers\Housekeeper\HousekeepingController as HousekeeperHousekeepingController;
use App\Http\Controllers\Housekeeper\RoomController as HousekeeperRoomController;
use App\Http\Controllers\Receptionist\BookingOnlineController as ReceptionistBookingOnlineController;
use App\Http\Controllers\Receptionist\ChargeController as ReceptionistChargeController;
use App\Http\Controllers\Receptionist\CheckInOutController as ReceptionistCheckInOutController;
use App\Http\Controllers\Receptionist\DashboardController as ReceptionistDashboardController;
use App\Http\Controllers\Receptionist\GuestController as ReceptionistGuestController;
use App\Http\Controllers\Receptionist\PaymentController as ReceptionistPaymentController;
use App\Http\Controllers\Receptionist\ReservationController as ReceptionistReservationController;
use App\Http\Controllers\Receptionist\RoomController as ReceptionistRoomController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->prefix('v1')->group(function () {

    Route::post('/auth/login', [LoginController::class, 'login']);

    Route::get('/guest/landing', [LandingPageController::class, 'index']);
    Route::get('/guest/room-types', [LandingPageController::class, 'roomTypes']);
    Route::get('/guest/room-types/{roomType}', [LandingPageController::class, 'roomTypeDetail']);
    Route::post('/guest/booking', [BookingController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/guest/track/{code}', [TrackBookingController::class, 'show'])->middleware('throttle:30,1');
    Route::put('/guest/track/{code}/cancel', [TrackBookingController::class, 'cancel'])->middleware('throttle:30,1');

    Route::middleware('auth')->group(function () {

        Route::post('/auth/logout', [LoginController::class, 'logout']);
        Route::get('/auth/user', [LoginController::class, 'user']);
        Route::get('/notifications', [NotificationController::class, 'index']);

        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/dashboard', [DashboardController::class, 'index']);
            Route::get('/admin/reports', [ReportController::class, 'index']);

            Route::apiResource('/admin/room-types', RoomTypeController::class);
            Route::apiResource('/admin/rooms', RoomController::class)->except(['show']);
            Route::patch('/admin/rooms/{room}/status', [RoomController::class, 'updateStatus']);
            Route::apiResource('/admin/facilities', FacilityController::class);
            Route::apiResource('/admin/staff', StaffController::class)->except(['show']);

            Route::get('/admin/settings', [SettingController::class, 'index']);
            Route::put('/admin/settings', [SettingController::class, 'update']);

            Route::get('/admin/booking-online', [BookingOnlineController::class, 'index']);
            Route::put('/admin/booking-online/{reservation}/confirm', [BookingOnlineController::class, 'confirm']);
            Route::put('/admin/booking-online/{reservation}/reject', [BookingOnlineController::class, 'reject']);

            Route::get('/admin/reservations', [ReservationController::class, 'index']);
            Route::post('/admin/reservations', [ReservationController::class, 'store']);
            Route::get('/admin/reservations/{reservation}', [ReservationController::class, 'show']);
            Route::put('/admin/reservations/{reservation}', [ReservationController::class, 'update']);
            Route::delete('/admin/reservations/{reservation}', [ReservationController::class, 'destroy']);
            Route::post('/admin/reservations/{reservation}/charges', [ChargeController::class, 'store']);
            Route::delete('/admin/charges/{charge}', [ChargeController::class, 'destroy']);

            Route::get('/admin/check-in-out', [CheckInOutController::class, 'index']);
            Route::put('/admin/check-in-out/{reservation}/check-in', [CheckInOutController::class, 'checkIn']);
            Route::put('/admin/check-in-out/{reservation}/check-out', [CheckInOutController::class, 'checkOut']);

            Route::get('/admin/payments', [PaymentController::class, 'index']);
            Route::post('/admin/payments', [PaymentController::class, 'store']);
            Route::get('/admin/payments/{payment}/invoice', [PaymentController::class, 'invoice']);
            Route::get('/admin/payments/{payment}/invoice/pdf', [PaymentController::class, 'invoicePdf']);

            Route::get('/admin/guests', [GuestController::class, 'index']);
            Route::get('/admin/guests/{guest}', [GuestController::class, 'show']);

            Route::get('/admin/housekeeping', [HousekeepingController::class, 'index']);
            Route::patch('/admin/housekeeping/{room}/done', [HousekeepingController::class, 'done']);
        });

        Route::middleware('role:receptionist')->group(function () {
            Route::get('/receptionist/dashboard', [ReceptionistDashboardController::class, 'index']);
            Route::get('/receptionist/rooms', [ReceptionistRoomController::class, 'index']);

            Route::get('/receptionist/booking-online', [ReceptionistBookingOnlineController::class, 'index']);
            Route::put('/receptionist/booking-online/{reservation}/confirm', [ReceptionistBookingOnlineController::class, 'confirm']);
            Route::put('/receptionist/booking-online/{reservation}/reject', [ReceptionistBookingOnlineController::class, 'reject']);

            Route::get('/receptionist/reservations', [ReceptionistReservationController::class, 'index']);
            Route::post('/receptionist/reservations', [ReceptionistReservationController::class, 'store']);
            Route::get('/receptionist/reservations/{reservation}', [ReceptionistReservationController::class, 'show']);
            Route::put('/receptionist/reservations/{reservation}', [ReceptionistReservationController::class, 'update']);
            Route::delete('/receptionist/reservations/{reservation}', [ReceptionistReservationController::class, 'destroy']);
            Route::post('/receptionist/reservations/{reservation}/charges', [ReceptionistChargeController::class, 'store']);
            Route::delete('/receptionist/charges/{charge}', [ReceptionistChargeController::class, 'destroy']);

            Route::get('/receptionist/check-in-out', [ReceptionistCheckInOutController::class, 'index']);
            Route::put('/receptionist/check-in-out/{reservation}/check-in', [ReceptionistCheckInOutController::class, 'checkIn']);
            Route::put('/receptionist/check-in-out/{reservation}/check-out', [ReceptionistCheckInOutController::class, 'checkOut']);

            Route::get('/receptionist/payments', [ReceptionistPaymentController::class, 'index']);
            Route::post('/receptionist/payments', [ReceptionistPaymentController::class, 'store']);
            Route::get('/receptionist/payments/{payment}/invoice', [ReceptionistPaymentController::class, 'invoice']);
            Route::get('/receptionist/payments/{payment}/invoice/pdf', [ReceptionistPaymentController::class, 'invoicePdf']);

            Route::get('/receptionist/guests', [ReceptionistGuestController::class, 'index']);
            Route::get('/receptionist/guests/{guest}', [ReceptionistGuestController::class, 'show']);
        });

        Route::middleware('role:housekeeper')->group(function () {
            Route::get('/housekeeper/rooms', [HousekeeperRoomController::class, 'index']);
            Route::get('/housekeeper/housekeeping', [HousekeeperHousekeepingController::class, 'index']);
            Route::patch('/housekeeper/housekeeping/{room}/done', [HousekeeperHousekeepingController::class, 'done']);
        });
    });
});
