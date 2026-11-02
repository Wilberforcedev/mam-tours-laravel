<?php

namespace App\Services;

use App\Models\Car;

class PricingService
{
    public function quote(Car $car, string $startDate, string $endDate, array $addOns = []): array
    {
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->startOfDay();
        if ($end->lessThanOrEqualTo($start)) {
            throw new \InvalidArgumentException('Return date must be after pickup date.');
        }

        $days = (int) $start->diffInDays($end);
        $minDays = max(1, (int) config('rental.minimum_days', 1));
        $maxDays = max($minDays, (int) config('rental.maximum_days', 30));
        if ($days < $minDays || $days > $maxDays) {
            throw new \InvalidArgumentException("Rental duration must be between {$minDays} and {$maxDays} days.");
        }

        $dailyRate = (int) $car->dailyRate;
        $base = $dailyRate * $days;
        $weekendDays = 0;
        for ($i = 0; $i < $days; $i++) {
            if ($start->copy()->addDays($i)->isWeekend()) {
                $weekendDays++;
            }
        }
        $base += (int) round($dailyRate * (float) config('rental.weekend_surcharge_rate', 0.10) * $weekendDays);
        if ($days >= 7) {
            $base = (int) round($base * (1 - (float) config('rental.weekly_discount_rate', 0.10)));
        }

        $driverFee = (int) config('rental.additional_driver_daily_fee', 50000);
        $childSeatFee = (int) config('rental.child_seat_daily_fee', 10000);
        $addOnTotal = (!empty($addOns['driver']) ? $driverFee * $days : 0)
            + (!empty($addOns['childSeat']) ? $childSeatFee * $days : 0);
        $deliveryFee = !empty($addOns['delivery']) ? max(0, (int) config('rental.delivery_fee', 0)) : 0;
        $grossSubtotal = $base + $addOnTotal + $deliveryFee;
        $discountCode = strtoupper(trim((string) ($addOns['discountCode'] ?? '')));
        $discountRate = 0.0;
        if ($discountCode !== '') {
            $configuredDiscounts = config('rental.discount_codes', []);
            $discountRate = (float) ($configuredDiscounts[$discountCode] ?? 0);
            if ($discountRate <= 0 || $discountRate > 1) {
                throw new \InvalidArgumentException('The discount code is invalid or inactive.');
            }
        }
        $discountAmount = (int) round($grossSubtotal * $discountRate);
        $subtotal = max(0, $grossSubtotal - $discountAmount);
        $taxRate = max(0, (float) config('rental.tax_rate', 0.18));
        $taxes = (int) round($subtotal * $taxRate);
        $depositRate = min(1, max(0, (float) config('rental.deposit_rate', 0.20)));
        $deposit = (int) round($subtotal * $depositRate);

        return [
            'currency' => strtoupper((string) config('rental.currency', 'UGX')),
            'days' => $days,
            'base' => $base,
            'addOnTotal' => $addOnTotal,
            'deliveryFee' => $deliveryFee,
            'discountCode' => $discountCode ?: null,
            'discountAmount' => $discountAmount,
            'subtotal' => $subtotal,
            'taxRate' => $taxRate,
            'taxes' => $taxes,
            'deposit' => $deposit,
            'total' => $subtotal + $taxes,
            'pricingVersion' => 1,
        ];
    }
}
