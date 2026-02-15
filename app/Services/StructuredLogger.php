<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Booking;

class StructuredLogger
{
    /**
     * Log with enriched context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $enrichedContext = array_merge($context, [
            'request_id' => request()->id() ?? uniqid('cli_'),
            'user_id' => auth()->id(),
            'environment' => app()->environment(),
            'timestamp' => now()->toIso8601String(),
            'ip_address' => request()->ip() ?? 'N/A',
            'user_agent' => request()->userAgent() ?? 'N/A',
        ]);

        Log::log($level, $message, $enrichedContext);
    }

    /**
     * Log authentication attempt
     */
    public function logAuthentication(User $user, bool $success, string $ip): void
    {
        $this->log($success ? 'info' : 'warning', 'Authentication attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'success' => $success,
            'ip_address' => $ip,
            'event_type' => 'authentication',
        ]);
    }

    /**
     * Log booking event
     */
    public function logBookingEvent(Booking $booking, string $action): void
    {
        $this->log('info', "Booking $action", [
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'car_id' => $booking->car_id,
            'action' => $action,
            'status' => $booking->status,
            'event_type' => 'booking',
        ]);
    }

    /**
     * Log payment event
     */
    public function logPaymentEvent(Booking $booking, string $action, array $details = []): void
    {
        $this->log('info', "Payment $action", array_merge([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'action' => $action,
            'payment_method' => $booking->payment_method,
            'payment_status' => $booking->payment_status,
            'event_type' => 'payment',
        ], $details));
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->log('warning', "Security event: $event", array_merge([
            'event_type' => 'security',
            'event' => $event,
        ], $context));
    }

    /**
     * Log API request
     */
    public function logApiRequest(string $endpoint, string $method, int $statusCode, float $duration): void
    {
        $this->log('info', 'API request', [
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'event_type' => 'api_request',
        ]);
    }
}
