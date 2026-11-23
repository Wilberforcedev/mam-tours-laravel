<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Register a new webhook endpoint
     */
    public function registerEndpoint(string $url, array $events = []): array
    {
        $this->assertPublicWebhookUrl($url);
        $secret = Str::random(64);
        
        $id = DB::table('webhook_endpoints')->insertGetId([
            'url' => $url,
            'secret' => $secret,
            'events' => json_encode($events),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id' => $id,
            'url' => $url,
            'secret' => $secret,
            'events' => $events,
        ];
    }

    /**
     * Dispatch a webhook event
     */
    public function dispatch(string $eventType, array $payload): void
    {
        $endpoints = DB::table('webhook_endpoints')
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            $events = json_decode($endpoint->events, true) ?? [];
            
            // If endpoint has specific events, check if this event matches
            if (!empty($events) && !in_array($eventType, $events)) {
                continue;
            }

            // Create delivery record
            $deliveryId = DB::table('webhook_deliveries')->insertGetId([
                'webhook_endpoint_id' => $endpoint->id,
                'event_type' => $eventType,
                'payload' => json_encode($payload),
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Queue the delivery
            \App\Jobs\DeliverWebhook::dispatch($deliveryId);
        }
    }

    /**
     * Deliver a webhook
     */
    public function deliver(int $deliveryId): bool
    {
        $delivery = DB::table('webhook_deliveries')->find($deliveryId);
        if (!$delivery) {
            return false;
        }

        $endpoint = DB::table('webhook_endpoints')->find($delivery->webhook_endpoint_id);
        if (!$endpoint || !$endpoint->is_active) {
            return false;
        }

        // Increment attempts
        DB::table('webhook_deliveries')
            ->where('id', $deliveryId)
            ->increment('attempts');

        try {
            $this->assertPublicWebhookUrl($endpoint->url);
            $payload = json_decode($delivery->payload, true);
            $signature = $this->generateSignature($payload, $endpoint->secret);

            $response = Http::timeout(10)
                ->withOptions(['allow_redirects' => false])
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $delivery->event_type,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint->url, $payload);

            $success = $response->successful();

            DB::table('webhook_deliveries')
                ->where('id', $deliveryId)
                ->update([
                    'status' => $success ? 'delivered' : 'failed',
                    'response_code' => $response->status(),
                    'response_body' => substr($response->body(), 0, 1000),
                    'delivered_at' => $success ? now() : null,
                    'updated_at' => now(),
                ]);

            return $success;
        } catch (\Exception $e) {
            DB::table('webhook_deliveries')
                ->where('id', $deliveryId)
                ->update([
                    'status' => 'failed',
                    'response_body' => substr($e->getMessage(), 0, 1000),
                    'updated_at' => now(),
                ]);

            logger()->error('Webhook delivery failed', [
                'delivery_id' => $deliveryId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate HMAC signature for webhook payload
     */
    public function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Verify webhook signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Reject webhook destinations that could reach local or private network services.
     */
    private function assertPublicWebhookUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim(trim((string) ($parts['host'] ?? ''), '[]'), '.'));

        if (!in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || in_array($host, ['localhost', 'metadata.google.internal'], true)
            || preg_match('/\.(?:localhost|local|internal|test)$/i', $host)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['url' => ['Webhook URL must use a public host.']]);
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses = [$host];
        } else {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            $addresses = [];

            foreach (is_array($records) ? $records : [] as $record) {
                if (isset($record['ip'])) {
                    $addresses[] = $record['ip'];
                } elseif (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }

        if (empty($addresses)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['url' => ['Webhook host must resolve to a public IP address.']]);
        }

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw \Illuminate\Validation\ValidationException::withMessages(['url' => ['Webhook URL cannot target private or reserved IP addresses.']]);
            }
        }
    }

    /**
     * Get webhook delivery status
     */
    public function getDeliveryStatus(int $deliveryId): ?array
    {
        $delivery = DB::table('webhook_deliveries')->find($deliveryId);
        
        if (!$delivery) {
            return null;
        }

        return [
            'id' => $delivery->id,
            'event_type' => $delivery->event_type,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'response_code' => $delivery->response_code,
            'delivered_at' => $delivery->delivered_at,
        ];
    }
}
