<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\FinancialTransaction;
use App\Models\VehicleMaintenanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function maintenanceIndex(Request $request)
    {
        $query = VehicleMaintenanceRecord::with('car')->orderByDesc('scheduled_for');
        if ($request->filled('car_id')) $query->where('car_id', (int) $request->input('car_id'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        return response()->json($query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))));
    }

    public function maintenanceStore(Request $request)
    {
        $data = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'type' => 'required|in:scheduled_service,repair,accident,inspection',
            'status' => 'required|in:scheduled,in_progress,completed,canceled',
            'description' => 'required|string|max:5000',
            'odometer_km' => 'nullable|integer|min:0',
            'cost_minor' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'scheduled_for' => 'nullable|date',
            'completed_on' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'licence_expiry' => 'nullable|date',
            'blocks_rental' => 'sometimes|boolean',
        ]);
        $data['created_by'] = $request->user()->id;
        $record = DB::transaction(function () use ($data) {
            $record = VehicleMaintenanceRecord::create($data);
            if ($record->blocks_rental && ($record->status === 'in_progress'
                || ($record->status === 'scheduled' && $record->scheduled_for && $record->scheduled_for->lte(now()->toDateString())))) {
                Car::whereKey($record->car_id)->update(['isAvailable' => false]);
            }
            return $record;
        });
        \App\Models\AuditLog::create([
            'action' => 'fleet.maintenance.created',
            'details' => ['maintenance_id' => $record->id, 'car_id' => $record->car_id, 'status' => $record->status, 'created_by' => $request->user()->id],
            'at' => now(),
        ]);
        return response()->json(['data' => $record->load('car')], 201);
    }

    public function maintenanceUpdate(Request $request, $id)
    {
        $record = VehicleMaintenanceRecord::findOrFail($id);
        $data = $request->validate([
            'type' => 'sometimes|in:scheduled_service,repair,accident,inspection',
            'status' => 'sometimes|in:scheduled,in_progress,completed,canceled',
            'description' => 'sometimes|string|max:5000',
            'odometer_km' => 'nullable|integer|min:0',
            'cost_minor' => 'nullable|integer|min:0',
            'currency' => 'nullable|string|size:3',
            'scheduled_for' => 'nullable|date',
            'completed_on' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'licence_expiry' => 'nullable|date',
            'blocks_rental' => 'sometimes|boolean',
        ]);
        $oldStatus = $record->status;
        $record->update($data);
        $blocked = VehicleMaintenanceRecord::where('car_id', $record->car_id)
            ->where('blocks_rental', true)
            ->where(function ($q) {
                $q->where('status', 'in_progress')
                  ->orWhere(function ($scheduled) {
                      $scheduled->where('status', 'scheduled')
                          ->where(function ($dates) {
                              $dates->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', now()->toDateString());
                          });
                  });
            })->exists();
        if (!$blocked) {
            // A vehicle is available only if no active booking overlaps now and no maintenance blocks it.
            Car::whereKey($record->car_id)->update(['isAvailable' => true]);
        } else {
            Car::whereKey($record->car_id)->update(['isAvailable' => false]);
        }
        \App\Models\AuditLog::create([
            'action' => 'fleet.maintenance.updated',
            'details' => ['maintenance_id' => $record->id, 'car_id' => $record->car_id, 'old_status' => $oldStatus, 'new_status' => $record->status, 'changed_by' => $request->user()->id],
            'at' => now(),
        ]);
        return response()->json(['data' => $record->fresh()->load('car')]);
    }

    public function financialSummary(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        validator(['from' => $from, 'to' => $to], [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ])->validate();

        $query = FinancialTransaction::whereBetween(DB::raw('DATE(COALESCE(occurred_at, created_at))'), [$from, $to]);
        $totals = (clone $query)->select('currency', 'type', 'direction', 'status', DB::raw('SUM(amount_minor) as amount_minor'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('currency', 'type', 'direction', 'status')->get();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'ledger_totals' => $totals,
            'note' => 'Totals are grouped by currency, transaction type and status; do not combine different currencies.',
        ]);
    }

    public function bookingsReport(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        validator(['from' => $from, 'to' => $to], [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ])->validate();

        $bookings = \App\Models\Booking::with('car')
            ->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->get();
        $paidBookings = $bookings->where('payment_status', 'completed');
        $revenueByCurrency = $paidBookings->groupBy(function ($booking) {
            return strtoupper((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
        })->map(function ($items, $currency) {
            return [
                'currency' => $currency,
                'amount' => $items->sum(function ($booking) {
                    return (float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0);
                }),
                'bookings' => $items->count(),
            ];
        })->values();
        $cashCollectedByCurrency = FinancialTransaction::where('type', 'payment')->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(COALESCE(occurred_at, created_at))'), [$from, $to])
            ->select('currency', DB::raw('SUM(amount_minor) as amount_minor'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('currency')->get();
        $fleet = Car::withCount(['bookings as active_bookings' => function ($q) {
            $q->whereIn('status', ['confirmed', 'in_use']);
        }])->get();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'booking_count' => $bookings->count(),
            'cancellations' => $bookings->where('status', 'canceled')->count(),
            'completed_paid_booking_value_by_currency' => $revenueByCurrency,
            'ledger_cash_collected_minor_units_by_currency' => $cashCollectedByCurrency,
            'fleet' => $fleet,
            'note' => 'Booking values are in quoted currency; ledger collection is in minor units and must be interpreted by currency.',
        ]);
    }

    public function managementSummary(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        validator(['from' => $from, 'to' => $to], [
            'from' => 'required|date_format:Y-m-d',
            'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ])->validate();

        $periodDays = max(1, \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1);
        $cars = \App\Models\Car::with(['bookings' => function ($q) use ($from, $to) {
            $q->whereIn('status', ['confirmed', 'in_use', 'completed', 'returned'])
              ->where('startDate', '<', \Carbon\Carbon::parse($to)->addDay())
              ->where('endDate', '>', $from);
        }])->get();

        $fleet = $cars->map(function ($car) use ($periodDays, $from, $to) {
            $rentalDays = 0;
            foreach ($car->bookings as $booking) {
                $start = max(strtotime($from), strtotime((string) $booking->startDate));
                $end = min(strtotime(\Carbon\Carbon::parse($to)->addDay()->toDateString()), strtotime((string) $booking->endDate));
                if ($end > $start) $rentalDays += (int) floor(($end - $start) / 86400);
            }
            $paid = $car->bookings->where('payment_status', 'completed')->sum(function ($booking) {
                return (float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0);
            });
            $maintenanceCosts = VehicleMaintenanceRecord::where('car_id', $car->id)
                ->whereBetween(DB::raw('DATE(COALESCE(completed_on, created_at))'), [$from, $to])
                ->select('currency', DB::raw('SUM(cost_minor) as cost_minor'))
                ->groupBy('currency')->get()->keyBy(function ($item) { return strtoupper($item->currency); });
            $revenueByCurrency = $car->bookings->where('payment_status', 'completed')
                ->groupBy(fn ($b) => strtoupper((string) ($b->pricing['currency'] ?? config('rental.currency', 'UGX'))))
                ->map(fn ($items, $currency) => [
                    'currency' => $currency,
                    'amount' => $items->sum(fn ($b) => (float) ($b->pricing['total'] ?? $b->totalPrice ?? 0)),
                ]);
            $profitByCurrency = $revenueByCurrency->map(function ($revenue, $currency) use ($maintenanceCosts) {
                $costMinor = (int) ($maintenanceCosts->get($currency)->cost_minor ?? 0);
                return [
                    'currency' => $currency,
                    'revenue' => $revenue['amount'],
                    'maintenance_cost_minor_units' => $costMinor,
                    'gross_profit_after_maintenance_estimate' => round($revenue['amount'] - ($costMinor / 100), 2),
                ];
            })->values();
            return [
                'car_id' => $car->id,
                'vehicle' => trim($car->brand . ' ' . $car->model),
                'utilization_percent' => round(min(100, ($rentalDays / $periodDays) * 100), 2),
                'paid_booking_value_by_currency' => $revenueByCurrency->values(),
                'maintenance_costs_by_currency' => $maintenanceCosts->values(),
                'gross_profit_after_maintenance_estimate_by_currency' => $profitByCurrency,
            ];
        });

        $overdue = \App\Models\Booking::with('car')->where('status', 'in_use')->where('endDate', '<', now())->get();
        $outstanding = \App\Models\Booking::whereIn('status', ['pending', 'reserved', 'confirmed', 'in_use'])->get()
            ->map(function ($booking) {
                $currency = strtoupper((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
                $quoted = (int) round(((float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0)) * 100);
                $paid = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')
                    ->where('type', 'payment')->sum('amount_minor');
                $refunded = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')
                    ->where('type', 'refund')->sum('amount_minor');
                $adjustmentDebits = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'adjustment')->where('direction', 'debit')->sum('amount_minor');
                $adjustmentCredits = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'adjustment')->where('direction', 'credit')->sum('amount_minor');
                return ['booking_id' => $booking->id, 'currency' => $currency, 'outstanding_minor_units' => max(0, $quoted - $paid + $refunded + $adjustmentDebits - $adjustmentCredits)];
            })->filter(fn ($item) => $item['outstanding_minor_units'] > 0)->values();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'fleet_utilization_and_profitability' => $fleet,
            'overdue_rentals' => $overdue,
            'outstanding_balances' => $outstanding,
            'maintenance_due_or_expiring' => VehicleMaintenanceRecord::where(function ($q) use ($to) {
                $q->whereBetween('scheduled_for', [now()->toDateString(), $to])
                  ->orWhereBetween('insurance_expiry', [now()->toDateString(), $to])
                  ->orWhereBetween('licence_expiry', [now()->toDateString(), $to]);
            })->with('car')->get(),
            'note' => 'Values are grouped by currency where applicable. Outstanding balances and maintenance costs are in minor units.',
        ]);
    }

    public function transactionsIndex(Request $request)
    {
        $query = FinancialTransaction::with(['booking', 'user'])->latest();
        if ($request->filled('booking_id')) $query->where('booking_id', (int) $request->input('booking_id'));
        if ($request->filled('type')) $query->where('type', $request->input('type'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        if ($request->filled('currency')) $query->where('currency', strtoupper($request->input('currency')));
        if ($request->filled('from')) $query->whereDate('occurred_at', '>=', $request->input('from'));
        if ($request->filled('to')) $query->whereDate('occurred_at', '<=', $request->input('to'));
        return response()->json($query->paginate(min(100, max(1, (int) $request->input('per_page', 20)))));
    }

    public function transactionReceipt($id)
    {
        $transaction = FinancialTransaction::with(['booking.car', 'user'])->findOrFail($id);
        if ($transaction->status !== 'completed') {
            return response()->json(['message' => 'Receipts are available only for completed transactions.'], 422);
        }
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('invoices.transaction-receipt', compact('transaction'));
        return $pdf->download('receipt-transaction-' . $transaction->id . '.pdf');
    }

    public function recordFinancialTransaction(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'type' => 'required|in:payment,refund,deposit_received,deposit_returned,adjustment',
            'direction' => 'required_if:type,adjustment|nullable|in:debit,credit',
            'amount_minor' => 'required|integer|min:1',
            'currency' => 'required|string|in:UGX,USD,ugx,usd',
            'provider' => 'required|in:cash,bank_transfer,manual',
            'provider_reference' => 'nullable|string|max:255',
            'idempotency_key' => 'required|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);
        if ($data['provider'] === 'bank_transfer' && empty($data['provider_reference'])) {
            return response()->json(['message' => 'A bank transfer reference is required.'], 422);
        }
        $booking = \App\Models\Booking::findOrFail($data['booking_id']);
        $currency = strtoupper($data['currency']);
        $bookingCurrency = strtoupper((string) ($booking->pricing['currency'] ?? config('rental.currency', 'UGX')));
        if ($currency !== $bookingCurrency) {
            return response()->json(['message' => 'Transaction currency must match the booking currency.'], 422);
        }

        try {
            $transaction = DB::transaction(function () use ($data, $request, $booking, $currency) {
                $existing = FinancialTransaction::where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ((int) $existing->booking_id !== (int) $booking->id
                        || (int) $existing->amount_minor !== (int) $data['amount_minor']
                        || strtoupper($existing->currency) !== $currency
                        || $existing->type !== $data['type']
                        || $existing->provider !== $data['provider']
                        || ($existing->direction ?? null) !== ($data['direction'] ?? null)
                        || (($data['provider_reference'] ?? null) !== null && $existing->provider_reference !== $data['provider_reference'])) {
                        throw new \RuntimeException('Idempotency key was already used for a different transaction.');
                    }
                    return $existing;
                }
                $lockedBooking = \App\Models\Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
                if ($data['type'] === 'refund') {
                    if (!in_array($lockedBooking->status, ['canceled', 'cancelled'], true)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['type' => ['Cancel the booking before recording a refund.']]);
                    }
                    $paidForRefund = (int) FinancialTransaction::where('booking_id', $lockedBooking->id)->where('status', 'completed')->where('type', 'payment')->sum('amount_minor');
                    $alreadyRefunded = (int) FinancialTransaction::where('booking_id', $lockedBooking->id)->where('status', 'completed')->where('type', 'refund')->sum('amount_minor');
                    if ((int) $data['amount_minor'] > max(0, $paidForRefund - $alreadyRefunded)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['amount_minor' => ['Refund amount exceeds the unreimbursed amount received.']]);
                    }
                }
                if ($data['type'] === 'deposit_received') {
                    $depositCap = (int) round(((float) ($lockedBooking->pricing['deposit'] ?? 0)) * 100);
                    $alreadyReceived = (int) FinancialTransaction::where('booking_id', $lockedBooking->id)->where('status', 'completed')->where('type', 'deposit_received')->sum('amount_minor');
                    if ($alreadyReceived + (int) $data['amount_minor'] > $depositCap) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['amount_minor' => ['Deposit received exceeds the quoted refundable deposit.']]);
                    }
                }
                if ($data['type'] === 'deposit_returned') {
                    $received = (int) FinancialTransaction::where('booking_id', $lockedBooking->id)->where('status', 'completed')->where('type', 'deposit_received')->sum('amount_minor');
                    $returned = (int) FinancialTransaction::where('booking_id', $lockedBooking->id)->where('status', 'completed')->where('type', 'deposit_returned')->sum('amount_minor');
                    if ((int) $data['amount_minor'] > max(0, $received - $returned)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['amount_minor' => ['Deposit return exceeds the refundable deposit balance.']]);
                    }
                }

                $transaction = FinancialTransaction::create([
                    'booking_id' => $lockedBooking->id,
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                    'direction' => $data['direction'] ?? null,
                    'status' => 'completed',
                    'amount_minor' => $data['amount_minor'],
                    'currency' => $currency,
                    'provider' => $data['provider'],
                    'provider_reference' => $data['provider_reference'] ?? null,
                    'idempotency_key' => $data['idempotency_key'],
                    'metadata' => ['notes' => $data['notes'] ?? null, 'recorded_by' => $request->user()->id],
                    'occurred_at' => now(),
                ]);
                if ($data['type'] === 'refund') {
                    $paidMinor = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'payment')->sum('amount_minor');
                    $refundedMinor = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'refund')->sum('amount_minor');
                    $booking->update(['payment_status' => $refundedMinor >= $paidMinor ? 'refunded' : 'partially_refunded']);
                }
                if ($data['type'] === 'payment' || $data['type'] === 'adjustment') {
                    $quotedMinor = (int) round(((float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0)) * 100);
                    $paidMinor = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')
                        ->where('type', 'payment')->sum('amount_minor');
                    $refundedMinor = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')
                        ->where('type', 'refund')->sum('amount_minor');
                    $adjustmentDebits = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'adjustment')->where('direction', 'debit')->sum('amount_minor');
                    $adjustmentCredits = (int) FinancialTransaction::where('booking_id', $booking->id)->where('status', 'completed')->where('type', 'adjustment')->where('direction', 'credit')->sum('amount_minor');
                    $status = ($paidMinor - $refundedMinor + $adjustmentCredits - $adjustmentDebits) >= $quotedMinor ? 'completed' : 'partially_paid';
                    $booking->update([
                        'payment_method' => $data['provider'],
                        'payment_status' => $status,
                    ]);
                }
                \App\Models\AuditLog::create([
                    'action' => 'finance.transaction.recorded',
                    'details' => ['transaction_id' => $transaction->id, 'booking_id' => $booking->id, 'type' => $data['type'], 'currency' => $currency, 'amount_minor' => $data['amount_minor'], 'recorded_by' => $request->user()->id],
                    'at' => now(),
                ]);
                return $transaction;
            });
            return response()->json(['data' => $transaction], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Idempotency key was already used for a different transaction.'], 409);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Transaction could not be recorded. Check the reference and idempotency key.'], 409);
        }
    }

    public function refundBooking(Request $request, $id)
    {
        $booking = \App\Models\Booking::findOrFail($id);
        if ($booking->status !== 'canceled') {
            return response()->json(['message' => 'Cancel the booking before requesting a refund.'], 422);
        }
        if ($booking->payment_status !== 'completed') {
            return response()->json(['message' => 'Only completed payments can be refunded.'], 422);
        }
        $result = app(\App\Services\PaymentService::class)->processRefund($booking);
        if (empty($result['success'])) {
            return response()->json(['message' => $result['error'] ?? 'Refund could not be processed.'], 422);
        }
        return response()->json(['message' => 'Refund submitted to the payment provider.', 'refund_reference' => $result['refund_id'] ?? null]);
    }

    public function updateStaffRole(Request $request, $id)
    {
        $data = $request->validate(['role' => 'required|in:admin,booking,finance,fleet,customer']);
        $user = \App\Models\User::findOrFail($id);
        if ((int) $request->user()->id === (int) $user->id && $data['role'] !== 'admin') {
            return response()->json(['message' => 'Administrators cannot remove their own administrator role.'], 422);
        }
        $oldRole = $user->role;
        $user->update(['role' => $data['role']]);
        \App\Models\AuditLog::create([
            'action' => 'staff.role.updated',
            'details' => ['target_user_id' => $user->id, 'old_role' => $oldRole, 'new_role' => $user->role, 'changed_by' => $request->user()->id],
            'at' => now(),
        ]);
        return response()->json(['id' => $user->id, 'role' => $user->role]);
    }
}
