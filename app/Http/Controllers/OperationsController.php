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
        if ($request->filled('car_id')) $query->where('car_id', $request->integer('car_id'));
        if ($request->filled('status')) $query->where('status', $request->input('status'));
        return response()->json($query->paginate(min(100, max(1, $request->integer('per_page', 20)))));
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
            if ($record->blocks_rental && in_array($record->status, ['scheduled', 'in_progress'], true)) {
                Car::whereKey($record->car_id)->update(['isAvailable' => false]);
            }
            return $record;
        });
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
        $record->update($data);
        $blocked = VehicleMaintenanceRecord::where('car_id', $record->car_id)
            ->where('blocks_rental', true)->whereIn('status', ['scheduled', 'in_progress'])->exists();
        if (!$blocked) {
            // A vehicle is available only if no active booking overlaps now and no maintenance blocks it.
            Car::whereKey($record->car_id)->update(['isAvailable' => true]);
        } else {
            Car::whereKey($record->car_id)->update(['isAvailable' => false]);
        }
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
        $totals = (clone $query)->select('currency', 'type', 'status', DB::raw('SUM(amount_minor) as amount_minor'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('currency', 'type', 'status')->get();

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
        $revenue = $bookings->where('payment_status', 'completed')->sum(function ($booking) {
            return (float) ($booking->pricing['total'] ?? $booking->totalPrice ?? 0);
        });
        $cashCollected = FinancialTransaction::where('type', 'payment')->where('status', 'completed')
            ->whereBetween(DB::raw('DATE(COALESCE(occurred_at, created_at))'), [$from, $to])->sum('amount_minor');
        $fleet = Car::withCount(['bookings as active_bookings' => function ($q) {
            $q->whereIn('status', ['confirmed', 'in_use']);
        }])->get();

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'booking_count' => $bookings->count(),
            'cancellations' => $bookings->where('status', 'canceled')->count(),
            'completed_paid_booking_value' => $revenue,
            'ledger_cash_collected_minor_units' => (int) $cashCollected,
            'fleet' => $fleet,
            'note' => 'Booking values are in quoted currency; ledger collection is in minor units and must be interpreted by currency.',
        ]);
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
