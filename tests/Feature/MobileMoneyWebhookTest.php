<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\NotificationService;
use Tests\TestCase;

class MobileMoneyWebhookTest extends TestCase
{
    public function test_unsigned_callback_cannot_complete_a_payment()
    {
        config(['app.webhook_secret' => 'test-shared-secret']);

        $booking = $this->pendingMobileMoneyBooking();

        $response = $this->postJson(route('webhooks.mobile-money'), [
            'transaction_id' => 'MM_test_' . $booking->id,
            'status' => 'completed',
        ]);

        $response->assertUnauthorized();
        $this->assertSame('pending', $booking->fresh()->payment_status);
    }

    public function test_callback_fails_closed_when_no_secret_is_configured()
    {
        config(['app.webhook_secret' => null]);

        $response = $this->postJson(route('webhooks.mobile-money'), [
            'transaction_id' => 'MM_unknown',
            'status' => 'completed',
        ]);

        $response->assertStatus(503);
    }

    public function test_valid_callback_completes_once_without_a_browser_session()
    {
        $secret = 'test-shared-secret';
        config(['app.webhook_secret' => $secret]);

        $booking = $this->pendingMobileMoneyBooking();
        $body = json_encode([
            'transaction_id' => 'MM_test_' . $booking->id,
            'status' => 'completed',
        ]);
        $signature = hash_hmac('sha256', $body, $secret);

        $notifications = \Mockery::mock(NotificationService::class);
        $notifications->shouldReceive('sendPaymentConfirmation')->once();
        $this->app->instance(NotificationService::class, $notifications);

        $sendCallback = function () use ($body, $signature) {
            return $this->call('POST', route('webhooks.mobile-money'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ], $body);
        };

        $sendCallback()->assertOk();
        $sendCallback()->assertOk();

        $this->assertSame('completed', $booking->fresh()->payment_status);
    }

    private function pendingMobileMoneyBooking(): Booking
    {
        $booking = Booking::factory()->create([
            'payment_method' => 'mobile_money',
            'payment_status' => 'pending',
            'payment' => [
                'transaction_id' => 'MM_test',
                'amount' => 125000,
            ],
        ]);

        $payment = $booking->payment;
        $payment['transaction_id'] = 'MM_test_' . $booking->id;
        $booking->update(['payment' => $payment]);

        return $booking;
    }
}
