<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Car;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BookingWithRealImagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        Storage::fake('public');
        $this->actingAs(User::factory()->customer()->create());
    }

    public function test_booking_with_real_image_from_gallery()
    {
        echo "\n\n=== BOOKING WITH REAL IMAGE TEST ===\n";
        
        // Create a test car
        $car = Car::factory()->create([
            'brand' => 'Toyota',
            'model' => 'Noah',
            'isAvailable' => true,
            'dailyRate' => 100000,
        ]);
        echo "✓ Car created: {$car->brand} {$car->model} (ID: {$car->id})\n";

        // Use a real image from public/images folder
        $imagePath = public_path('images/Noah.jpeg');
        
        if (!file_exists($imagePath)) {
            echo "✗ Image not found at: $imagePath\n";
            $this->fail("Image file not found");
        }
        
        echo "✓ Image found: $imagePath\n";
        
        // Create an uploaded file from the real image
        $file = new UploadedFile(
            $imagePath,
            'id-document.jpeg',
            'image/jpeg',
            null,
            true
        );
        
        echo "✓ File prepared: {$file->getClientOriginalName()} ({$file->getSize()} bytes)\n";

        // Prepare booking data
        $bookingData = [
            'carId' => $car->id,
            'customerName' => 'Test User',
            'customerEmail' => 'test@example.com',
            'customerPhone' => '+256700000000',
            'startDate' => now()->addDays(2)->toDateString(),
            'endDate' => now()->addDays(7)->toDateString(),
            'paymentMethod' => 'mtn_mobile_money',
            'mobileMoneyNumber' => '+256700000000',
            'idType' => 'nin',
            'idNumber' => '12345678',
            'idDocument' => $file,
        ];
        
        echo "✓ Booking data prepared\n";

        // Submit booking
        echo "\n→ Submitting booking to /api/bookings...\n";
        $response = $this->post('/api/bookings', $bookingData);

        echo "✓ Response status: {$response->status()}\n";
        
        // Assert response
        if ($response->status() !== 201) {
            echo "✗ Expected 201, got {$response->status()}\n";
            echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
            $this->fail("Booking creation failed with status {$response->status()}");
        }
        
        $response->assertStatus(201);
        echo "✓ Status code is 201 (Created)\n";

        // Check response structure
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
        echo "✓ Response structure is correct\n";

        // Get the booking data
        $bookingResponse = $response->json('booking');
        echo "\n✓ Booking created successfully!\n";
        echo "  - Booking ID: {$bookingResponse['id']}\n";
        echo "  - Customer: {$bookingResponse['customerName']}\n";
        echo "  - Email: {$bookingResponse['customerEmail']}\n";
        echo "  - Status: {$bookingResponse['status']}\n";
        echo "  - Total Price: UGX {$bookingResponse['totalPrice']}\n";
        echo "  - ID Type: {$bookingResponse['idType']}\n";
        echo "  - ID Number: {$bookingResponse['idNumber']}\n";
        echo "  - Document: {$bookingResponse['idDocument']}\n";

        // Assert booking was created in database
        $this->assertDatabaseHas('bookings', [
            'car_id' => $car->id,
            'customerName' => 'Test User',
            'customerEmail' => 'test@example.com',
            'customerPhone' => '+256700000000',
            'status' => 'pending',
            'idType' => 'nin',
            'idNumber' => '12345678',
        ]);
        echo "\n✓ Booking found in database\n";

        // Assert file was uploaded
        $booking = Booking::where('customerEmail', 'test@example.com')->first();
        $this->assertNotNull($booking->idDocument);
        Storage::disk('private')->assertExists($booking->idDocument);
        echo "✓ File path stored: {$booking->idDocument}\n";

        // Assert car is now unavailable
        $this->assertFalse($car->fresh()->isAvailable);
        echo "✓ Car marked as unavailable\n";

        echo "\n=== TEST PASSED ===\n";
        echo "✓ Booking created successfully with real image\n";
        echo "✓ File uploaded and stored\n";
        echo "✓ Car marked unavailable\n";
        echo "✓ All validations passed\n\n";
    }

    public function test_booking_with_different_image_formats()
    {
        $formats = [
            ['path' => public_path('images/Hilux.jpg'), 'name' => 'id.jpg', 'idType' => 'nin', 'idNumber' => '11111111'],
            ['path' => public_path('images/Alphard.jpeg'), 'name' => 'id.jpeg', 'idType' => 'passport', 'idNumber' => '22222222'],
        ];

        $asserted = 0;

        foreach ($formats as $format) {
            if (!file_exists($format['path'])) {
                continue;
            }

            $car = Car::factory()->create(['isAvailable' => true, 'dailyRate' => 100000]);
            $file = new UploadedFile($format['path'], $format['name'], 'image/jpeg', null, true);

            $response = $this->post('/api/bookings', [
                'carId' => $car->id,
                'customerName' => 'Format Test',
                'customerEmail' => 'format@test.com',
                'customerPhone' => '+256700000001',
                'startDate' => now()->addDays(2)->toDateString(),
                'endDate' => now()->addDays(7)->toDateString(),
                'paymentMethod' => 'cash',
                'idType' => $format['idType'],
                'idNumber' => $format['idNumber'],
                'idDocument' => $file,
            ]);

            $response->assertStatus(201);
            $asserted++;
        }

        if ($asserted === 0) {
            $this->markTestSkipped('No sample images available');
        }

        $this->assertSame(count($formats), $asserted);
    }

    public function test_complete_booking_workflow()
    {
        echo "\n\n=== COMPLETE BOOKING WORKFLOW TEST ===\n";
        
        // Step 1: Create car
        $car = Car::factory()->create([
            'brand' => 'Toyota',
            'model' => 'Prado',
            'isAvailable' => true,
            'dailyRate' => 150000,
        ]);
        echo "Step 1: ✓ Car created (Toyota Prado)\n";

        // Step 2: Prepare image
        $imagePath = public_path('images/Prado.jpg');
        if (!file_exists($imagePath)) {
            echo "Step 2: ✗ Image not found\n";
            $this->markTestSkipped("Image not found");
        }
        
        $file = new UploadedFile($imagePath, 'passport.jpg', 'image/jpeg', null, true);
        echo "Step 2: ✓ Image prepared\n";

        // Step 3: Submit booking
        $response = $this->post('/api/bookings', [
            'carId' => $car->id,
            'customerName' => 'John Doe',
            'customerEmail' => 'john@example.com',
            'customerPhone' => '+256700000000',
            'startDate' => now()->addDays(2)->toDateString(),
            'endDate' => now()->addDays(7)->toDateString(),
            'paymentMethod' => 'airtel_money',
            'mobileMoneyNumber' => '+256700000000',
            'idType' => 'passport',
            'idNumber' => 'AB123456',
            'idDocument' => $file,
        ]);
        
        echo "Step 3: ✓ Booking submitted\n";

        // Step 4: Verify response
        $response->assertStatus(201);
        $booking = $response->json('booking');
        echo "Step 4: ✓ Response received (Booking ID: {$booking['id']})\n";

        // Step 5: Verify database
        $this->assertDatabaseHas('bookings', [
            'id' => $booking['id'],
            'car_id' => $car->id,
            'customerName' => 'John Doe',
            'status' => 'pending',
        ]);
        echo "Step 5: ✓ Booking verified in database\n";

        // Step 6: Verify file
        $dbBooking = Booking::find($booking['id']);
        $this->assertNotNull($dbBooking->idDocument);
        echo "Step 6: ✓ File stored: {$dbBooking->idDocument}\n";

        // Step 7: Verify car unavailable
        $this->assertFalse($car->fresh()->isAvailable);
        echo "Step 7: ✓ Car marked unavailable\n";

        echo "\n=== WORKFLOW COMPLETE ===\n";
        echo "✓ All steps passed successfully!\n\n";
    }
}
