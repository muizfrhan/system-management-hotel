<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityAndIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_use_server_session(): void
    {
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withHeader('Origin', 'http://localhost:5173');

        User::factory()->create([
            'email' => 'session@example.com',
            'password' => Hash::make('secure-session-password'),
            'role' => 'admin',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'session@example.com',
            'password' => 'secure-session-password',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'session@example.com')
            ->assertJsonMissingPath('user.password');

        $this->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('role', 'admin');

        $this->postJson('/api/v1/auth/logout')->assertOk();
        $this->getJson('/api/v1/auth/user')->assertUnauthorized();
    }

    public function test_cors_allows_only_configured_frontend_origin(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/auth/login')
            ->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');

        $untrustedResponse = $this->withHeaders([
            'Origin' => 'https://untrusted.example',
            'Access-Control-Request-Method' => 'POST',
        ])->options('/api/v1/auth/login');

        $untrustedResponse->assertSuccessful();
        $this->assertNotSame('https://untrusted.example', $untrustedResponse->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_api_responses_include_security_headers(): void
    {
        $this->getJson('/api/v1/guest/landing')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderMissing('X-Powered-By');
    }

    public function test_internal_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/admin/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Autentikasi diperlukan.');
    }

    public function test_login_rate_limit_returns_429(): void
    {
        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withHeader('Origin', 'http://localhost:5173');

        User::factory()->create([
            'email' => 'limited@example.com',
            'role' => 'admin',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_bearer_token_is_not_accepted_by_cookie_session_api(): void
    {
        $this->withToken('unsupported-token')
            ->getJson('/api/v1/admin/dashboard')
            ->assertUnauthorized();
    }

    public function test_backend_enforces_role_isolation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'receptionist']), 'web')
            ->getJson('/api/v1/admin/dashboard')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'housekeeper']), 'web')
            ->getJson('/api/v1/receptionist/dashboard')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']), 'web')
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk();
    }

    public function test_manual_booking_enforces_room_capacity(): void
    {
        $roomType = $this->createRoomType(['capacity' => 2]);
        $room = $this->createRoom($roomType);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->withHeader('Idempotency-Key', 'manual-capacity-1')
            ->postJson('/api/v1/receptionist/reservations', [
                'guest_name' => 'Tamu Capacity',
                'guest_email' => 'capacity@example.com',
                'room_id' => $room->id,
                'check_in_date' => today()->addDay()->toDateString(),
                'check_out_date' => today()->addDays(3)->toDateString(),
                'number_of_guests' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('number_of_guests');

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_manual_booking_rejects_overlapping_reservation(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $guest = $this->createGuest();
        $this->createReservation($room, $guest, [
            'check_in_date' => today()->addDay()->toDateString(),
            'check_out_date' => today()->addDays(3)->toDateString(),
            'status' => 'confirmed',
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->withHeader('Idempotency-Key', 'manual-overlap-1')
            ->postJson('/api/v1/receptionist/reservations', [
                'guest_name' => 'Tamu Kedua',
                'guest_email' => 'second@example.com',
                'room_id' => $room->id,
                'check_in_date' => today()->addDays(2)->toDateString(),
                'check_out_date' => today()->addDays(4)->toDateString(),
                'number_of_guests' => 1,
            ])
            ->assertConflict();

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_pending_booking_cannot_be_confirmed_twice(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $reservation = $this->createReservation($room, $this->createGuest(), ['status' => 'pending']);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/booking-online/{$reservation->id}/confirm")
            ->assertOk();

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/booking-online/{$reservation->id}/confirm")
            ->assertConflict();

        $this->assertSame('confirmed', $reservation->fresh()->status);
    }

    public function test_pending_reservation_cannot_check_in(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $reservation = $this->createReservation($room, $this->createGuest(), ['status' => 'pending']);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/check-in-out/{$reservation->id}/check-in")
            ->assertConflict();

        $this->assertSame('pending', $reservation->fresh()->status);
        $this->assertSame('reserved', $room->fresh()->status);
    }

    public function test_checkout_is_idempotently_rejected_after_first_success(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), ['status' => 'checked_in']);
        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_price,
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/check-in-out/{$reservation->id}/check-out")
            ->assertOk();

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/check-in-out/{$reservation->id}/check-out")
            ->assertConflict();

        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('checked_out', $reservation->fresh()->status);
        $this->assertSame('cleaning', $room->fresh()->status);
    }

    public function test_checkout_rejects_unpaid_balance(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), ['status' => 'checked_in']);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/check-in-out/{$reservation->id}/check-out")
            ->assertConflict()
            ->assertJsonPath('message', 'Sisa tagihan harus dilunasi sebelum check-out.');

        $this->assertSame('checked_in', $reservation->fresh()->status);
        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_housekeeping_cannot_release_occupied_room(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $user = User::factory()->create(['role' => 'housekeeper']);

        $this->actingAs($user, 'web')
            ->patchJson("/api/v1/housekeeper/housekeeping/{$room->id}/done")
            ->assertConflict();

        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_payment_cannot_exceed_outstanding_balance(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), [
            'status' => 'checked_in',
            'total_price' => 100000,
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->withHeader('Idempotency-Key', 'payment-over-limit')
            ->postJson('/api/v1/receptionist/payments', [
                'reservation_id' => $reservation->id,
                'amount' => 100001,
                'payment_method' => 'cash',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_invoice_reports_partial_payment_as_unpaid(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), [
            'status' => 'checked_in',
            'total_price' => 100000,
        ]);
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 40000,
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $response = $this->actingAs($user, 'web')
            ->getJson("/api/v1/receptionist/payments/{$payment->id}/invoice")
            ->assertOk();

        $this->assertEquals(40000, $response->json('totals.paid_total'));
        $this->assertEquals(60000, $response->json('totals.remaining'));
        $this->assertSame('unpaid', $response->json('totals.settlement_status'));
    }

    public function test_public_tracking_returns_minimal_allowlisted_data(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $guest = $this->createGuest([
            'name' => 'Tamu Publik',
            'email' => 'public@example.com',
            'phone' => '08123456789',
        ]);
        $reservation = $this->createReservation($room, $guest, ['status' => 'pending']);
        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 50000,
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        Charge::create([
            'reservation_id' => $reservation->id,
            'description' => 'Room service',
            'category' => 'service',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);

        $response = $this->getJson("/api/v1/guest/track/{$reservation->reservation_code}")
            ->assertOk()
            ->assertJsonPath('guest.name', 'Tamu Publik')
            ->assertJsonMissingPath('guest.email')
            ->assertJsonMissingPath('guest.phone')
            ->assertJsonMissingPath('payments')
            ->assertJsonMissingPath('special_requests')
            ->assertJsonMissingPath('charges')
            ->assertJsonMissingPath('room.id');

        $this->assertSame($reservation->reservation_code, $response->json('reservation_code'));
    }

    public function test_public_cancellation_is_safe_to_retry(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $reservation = $this->createReservation($room, $this->createGuest(), ['status' => 'pending']);

        $this->putJson("/api/v1/guest/track/{$reservation->reservation_code}/cancel")
            ->assertOk();
        $this->putJson("/api/v1/guest/track/{$reservation->reservation_code}/cancel")
            ->assertOk()
            ->assertJsonPath('message', 'Reservasi sudah dibatalkan.');

        $this->assertSame('cancelled', $reservation->fresh()->status);
        $this->assertSame('available', $room->fresh()->status);
    }

    public function test_room_with_reservation_history_cannot_be_deleted(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $this->createReservation($room, $this->createGuest(), ['status' => 'confirmed']);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user, 'web')
            ->deleteJson("/api/v1/admin/rooms/{$room->id}")
            ->assertConflict();

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
    }

    public function test_room_type_with_reservation_history_cannot_be_deleted(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $this->createReservation($room, $this->createGuest(), ['status' => 'confirmed']);
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user, 'web')
            ->deleteJson("/api/v1/admin/room-types/{$roomType->id}")
            ->assertConflict();

        $this->assertDatabaseHas('room_types', ['id' => $roomType->id]);
    }

    public function test_room_type_can_be_updated_without_replacing_images(): void
    {
        $roomType = $this->createRoomType();
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/admin/room-types/{$roomType->id}", [
                'name' => 'Deluxe Updated',
                'keep_images' => json_encode([]),
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Deluxe Updated');
    }

    public function test_report_rejects_excessive_date_ranges(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/admin/reports?from=2020-01-01&to=2026-01-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to');
    }

    public function test_reservation_code_has_sufficient_entropy(): void
    {
        $code = Reservation::generateCode();

        $this->assertStringStartsWith('RSV', $code);
        $this->assertGreaterThanOrEqual(31, strlen($code));
    }

    public function test_public_booking_reuses_the_same_idempotency_key(): void
    {
        $roomType = $this->createRoomType();
        $this->createRoom($roomType);
        $payload = [
            'room_type_id' => $roomType->id,
            'name' => 'Tamu Idempotent',
            'email' => 'idempotent@example.com',
            'phone' => '08123456789',
            'check_in_date' => today()->addDay()->toDateString(),
            'check_out_date' => today()->addDays(3)->toDateString(),
            'number_of_guests' => 1,
        ];

        $this->withHeader('Idempotency-Key', 'public-booking-1')
            ->postJson('/api/v1/guest/booking', $payload)
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'public-booking-1')
            ->postJson('/api/v1/guest/booking', $payload)
            ->assertOk();

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_public_booking_rejects_reusing_a_key_for_different_data(): void
    {
        $roomType = $this->createRoomType();
        $this->createRoom($roomType);
        $payload = [
            'room_type_id' => $roomType->id,
            'name' => 'Tamu Awal',
            'email' => 'awal@example.com',
            'phone' => '08123456789',
            'check_in_date' => today()->addDay()->toDateString(),
            'check_out_date' => today()->addDays(3)->toDateString(),
            'number_of_guests' => 1,
        ];

        $this->withHeader('Idempotency-Key', 'public-booking-2')
            ->postJson('/api/v1/guest/booking', $payload)
            ->assertCreated();

        $this->withHeader('Idempotency-Key', 'public-booking-2')
            ->postJson('/api/v1/guest/booking', array_merge($payload, ['name' => 'Tamu Berbeda']))
            ->assertConflict();

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_payment_replay_with_the_same_idempotency_key_does_not_duplicate_payment(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), [
            'status' => 'checked_in',
            'total_price' => 100000,
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);
        $payload = [
            'reservation_id' => $reservation->id,
            'amount' => 40000,
            'payment_method' => 'cash',
        ];

        $this->actingAs($user, 'web')
            ->withHeader('Idempotency-Key', 'payment-replay-1')
            ->postJson('/api/v1/receptionist/payments', $payload)
            ->assertCreated();

        $this->actingAs($user, 'web')
            ->withHeader('Idempotency-Key', 'payment-replay-1')
            ->postJson('/api/v1/receptionist/payments', $payload)
            ->assertOk();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_check_in_before_reservation_date_is_rejected(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'reserved']);
        $reservation = $this->createReservation($room, $this->createGuest(), [
            'status' => 'confirmed',
            'check_in_date' => today()->addDay()->toDateString(),
            'check_out_date' => today()->addDays(3)->toDateString(),
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->putJson("/api/v1/receptionist/check-in-out/{$reservation->id}/check-in")
            ->assertConflict();

        $this->assertSame('confirmed', $reservation->fresh()->status);
        $this->assertSame('reserved', $room->fresh()->status);
    }

    public function test_paid_reservation_rejects_folio_changes(): void
    {
        $roomType = $this->createRoomType();
        $room = $this->createRoom($roomType, ['status' => 'occupied']);
        $reservation = $this->createReservation($room, $this->createGuest(), [
            'status' => 'checked_in',
            'total_price' => 100000,
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => 100000,
            'payment_method' => 'cash',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
        $user = User::factory()->create(['role' => 'receptionist']);

        $this->actingAs($user, 'web')
            ->postJson("/api/v1/receptionist/reservations/{$reservation->id}/charges", [
                'description' => 'Room service',
                'category' => 'service',
                'quantity' => 1,
                'unit_price' => 10000,
            ])
            ->assertConflict();

        $this->assertDatabaseCount('charges', 0);
    }

    public function test_public_booking_rejects_total_above_database_limit(): void
    {
        $roomType = $this->createRoomType(['base_price' => 99999999.99]);
        $this->createRoom($roomType);

        $this->withHeader('Idempotency-Key', 'booking-overflow-1')
            ->postJson('/api/v1/guest/booking', [
                'room_type_id' => $roomType->id,
                'name' => 'Tamu Overflow',
                'email' => 'overflow@example.com',
                'phone' => '08123456789',
                'check_in_date' => today()->toDateString(),
                'check_out_date' => today()->addDays(365)->toDateString(),
                'number_of_guests' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_type_id');

        $this->assertDatabaseCount('reservations', 0);
    }

    private function createRoomType(array $attributes = []): RoomType
    {
        return RoomType::create(array_merge([
            'name' => 'Deluxe '.uniqid(),
            'base_price' => 500000,
            'capacity' => 2,
            'size' => '32 m2',
            'bed_type' => 'King',
            'is_active' => true,
        ], $attributes));
    }

    private function createRoom(RoomType $roomType, array $attributes = []): Room
    {
        return Room::create(array_merge([
            'room_type_id' => $roomType->id,
            'room_number' => (string) random_int(1000, 999999),
            'floor' => '1',
            'status' => 'available',
        ], $attributes));
    }

    private function createGuest(array $attributes = []): Guest
    {
        return Guest::create(array_merge([
            'name' => 'Tamu',
            'email' => uniqid().'@example.com',
            'phone' => '08123456789',
        ], $attributes));
    }

    private function createReservation(Room $room, Guest $guest, array $attributes = []): Reservation
    {
        return Reservation::create(array_merge([
            'reservation_code' => Reservation::generateCode(),
            'guest_id' => $guest->id,
            'room_id' => $room->id,
            'check_in_date' => today()->addDay()->toDateString(),
            'check_out_date' => today()->addDays(3)->toDateString(),
            'number_of_guests' => 1,
            'status' => 'confirmed',
            'total_price' => 500000,
        ], $attributes));
    }
}
