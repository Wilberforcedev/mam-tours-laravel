<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Car;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BookingFileUploadTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');

        $this->user = User::factory()->customer()->create();
    }

    private function bookingData(Car $car, array $overrides = []): array
    {
        return array_merge([
            'carId' => $car->id,
            'customerName' => $this->user->name,
            'customerEmail' => $this->user->email,
            'customerPhone' => '+256700000000',
            'startDate' => now()->addDays(2)->toDateString(),
            'endDate' => now()->addDays(7)->toDateString(),
            'paymentMethod' => 'mtn_mobile_money',
            'mobileMoneyNumber' => '+256700000000',
            'idType' => 'nin',
            'idNumber' => '12345678',
        ], $overrides);
    }

    private function idFile(string $name = 'id.jpg'): UploadedFile
    {
        return UploadedFile::fake()->create($name, 100, 'image/jpeg');
    }

    public function test_can_create_booking_with_file_upload()
    {
        $car = Car::factory()->create([
            'isAvailable' => true,
            'dailyRate' => 100000,
        ]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'idDocument' => $this->idFile(),
        ]));

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'booking' => [
                'id',
                'car_id',
                'customerName',
                'customerEmail',
                'customerPhone',
                'startDate',
                'endDate',
                'status',
                'totalPrice',
                'idType',
                'idNumber',
                'idDocument',
            ],
            'redirect_url',
        ]);

        $this->assertDatabaseHas('bookings', [
            'car_id' => $car->id,
            'customerName' => $this->user->name,
            'status' => 'pending',
            'idType' => 'nin',
            'idNumber' => '12345678',
            'user_id' => $this->user->id,
        ]);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNotNull($booking->idDocument);
        Storage::disk('private')->assertExists($booking->idDocument);

        $this->assertFalse($car->fresh()->isAvailable);
    }

    public function test_booking_succeeds_without_id_document()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car));

        $response->assertStatus(201);

        $booking = Booking::where('user_id', $this->user->id)->first();
        $this->assertNull($booking->idDocument);
    }

    public function test_booking_validation_fails_with_invalid_file_type()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'idDocument' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('idDocument');
    }

    public function test_booking_validation_fails_with_oversized_file()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'idDocument' => UploadedFile::fake()->create('id.jpg', 6000, 'image/jpeg'),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('idDocument');
    }

    public function test_booking_validation_fails_with_invalid_dates()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'startDate' => now()->addDays(5)->toDateString(),
            'endDate' => now()->addDays(2)->toDateString(),
            'idDocument' => $this->idFile(),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('endDate');
    }

    public function test_booking_validation_rejects_past_dates()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'startDate' => now()->subDay()->toDateString(),
            'endDate' => now()->addDays(2)->toDateString(),
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('startDate');
    }

    public function test_booking_fails_if_car_unavailable()
    {
        $car = Car::factory()->create(['isAvailable' => false]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'idDocument' => $this->idFile(),
        ]));

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Car is not available for the selected dates']);
    }

    public function test_booking_with_passport_instead_of_nin()
    {
        $car = Car::factory()->create([
            'isAvailable' => true,
            'dailyRate' => 100000,
        ]);

        $response = $this->actingAs($this->user)->post('/api/bookings', $this->bookingData($car, [
            'paymentMethod' => 'airtel_money',
            'idType' => 'passport',
            'idNumber' => 'AB123456',
            'idDocument' => $this->idFile('passport.jpg'),
        ]));

        $response->assertStatus(201);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->user->id,
            'idType' => 'passport',
            'idNumber' => 'AB123456',
        ]);
    }

    public function test_guest_cannot_create_booking()
    {
        $car = Car::factory()->create(['isAvailable' => true]);

        $response = $this->postJson('/api/bookings', $this->bookingData($car));

        $response->assertStatus(401);
        $this->assertDatabaseCount('bookings', 0);
    }
}
