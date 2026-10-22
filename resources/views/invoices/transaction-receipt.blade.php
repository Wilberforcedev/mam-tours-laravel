<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #bbb; padding: 8px; text-align: left; }
        th { background: #f1f1f1; }
    </style>
</head>
<body>
    <h1>MAM Tours — Payment Receipt</h1>
    <p class="muted">Receipt reference: {{ $transaction->provider_reference ?: ('MAM-' . $transaction->id) }}</p>
    <p><strong>Transaction ID:</strong> {{ $transaction->id }}</p>
    <p><strong>Date:</strong> {{ optional($transaction->occurred_at ?: $transaction->created_at)->format('d M Y H:i') }}</p>
    <p><strong>Status:</strong> {{ strtoupper($transaction->status) }}</p>
    <table>
        <tr><th>Customer</th><td>{{ optional(optional($transaction->booking)->user)->name ?: optional($transaction->user)->name ?: optional($transaction->booking)->customerName ?: 'Customer' }}</td></tr>
        <tr><th>Booking</th><td>{{ $transaction->booking_id ?: '—' }}</td></tr>
        <tr><th>Vehicle</th><td>{{ optional(optional($transaction->booking)->car)->brand }} {{ optional(optional($transaction->booking)->car)->model }}</td></tr>
        <tr><th>Transaction type</th><td>{{ ucwords(str_replace('_', ' ', $transaction->type)) }}{{ $transaction->direction ? ' (' . strtoupper($transaction->direction) . ')' : '' }}</td></tr>
        <tr><th>Payment provider</th><td>{{ strtoupper($transaction->provider ?: 'MANUAL') }}</td></tr>
        <tr><th>Currency</th><td>{{ strtoupper($transaction->currency) }}</td></tr>
        <tr><th>Amount</th><td>{{ strtoupper($transaction->currency) === 'UGX' ? number_format($transaction->amount_minor / 100, 0) : number_format($transaction->amount_minor / 100, 2) }} {{ strtoupper($transaction->currency) }}</td></tr>
    </table>
    <p class="muted">Keep this receipt with your booking records. The receipt displays the amount in the transaction's currency. The ledger retains provider minor units for reconciliation.</p>
</body>
</html>
