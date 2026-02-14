<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - Booking #{{ $booking->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }
        .invoice-title {
            font-size: 20px;
            margin: 20px 0;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info, .customer-info {
            width: 48%;
        }
        .info-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            color: #007bff;
        }
        .info-item {
            margin-bottom: 5px;
        }
        .booking-details {
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .details-table th,
        .details-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        .details-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .pricing-table th,
        .pricing-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: right;
        }
        .pricing-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .pricing-table .description {
            text-align: left;
        }
        .total-row {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .payment-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">MAM Tours</div>
        <div>Car Rental Services</div>
        <div>Phone: +256 755-943973 | Email: info@mamtours.com</div>
    </div>

    <div class="invoice-title">
        <h2>INVOICE</h2>
    </div>

    <div class="invoice-details">
        <div class="invoice-info">
            <div class="info-title">Invoice Details</div>
            <div class="info-item"><strong>Invoice #:</strong> INV-{{ $booking->id }}-{{ date('Y') }}</div>
            <div class="info-item"><strong>Booking ID:</strong> #{{ $booking->id }}</div>
            <div class="info-item"><strong>Issue Date:</strong> {{ now()->format('M j, Y') }}</div>
            <div class="info-item"><strong>Status:</strong> 
                <span class="status-badge status-{{ $booking->payment_status }}">
                    {{ ucfirst($booking->payment_status) }}
                </span>
            </div>
        </div>

        <div class="customer-info">
            <div class="info-title">Customer Information</div>
            <div class="info-item"><strong>Name:</strong> {{ $booking->user->name }}</div>
            <div class="info-item"><strong>Email:</strong> {{ $booking->user->email }}</div>
            <div class="info-item"><strong>Phone:</strong> {{ $booking->user->phone }}</div>
            @if($booking->mobile_money_number)
            <div class="info-item"><strong>Mobile Money:</strong> {{ $booking->mobile_money_number }}</div>
            @endif
        </div>
    </div>

    <div class="booking-details">
        <h3>Booking Details</h3>
        <table class="details-table">
            <tr>
                <th>Vehicle</th>
                <td>{{ $booking->car->brand }} {{ $booking->car->model }} ({{ $booking->car->year }})</td>
            </tr>
            <tr>
                <th>License Plate</th>
                <td>{{ $booking->car->license_plate ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Rental Period</th>
                <td>{{ $booking->startDate->format('M j, Y') }} to {{ $booking->endDate->format('M j, Y') }}</td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>{{ $booking->startDate->diffInDays($booking->endDate) }} days</td>
            </tr>
            <tr>
                <th>Pickup Date</th>
                <td>{{ $booking->startDate->format('l, M j, Y \a\t g:i A') }}</td>
            </tr>
            <tr>
                <th>Return Date</th>
                <td>{{ $booking->endDate->format('l, M j, Y \a\t g:i A') }}</td>
            </tr>
        </table>
    </div>

    <div class="pricing-breakdown">
        <h3>Pricing Breakdown</h3>
        <table class="pricing-table">
            <thead>
                <tr>
                    <th class="description">Description</th>
                    <th>Days</th>
                    <th>Rate</th>
                    <th>Amount (UGX)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="description">Base Rental Fee</td>
                    <td>{{ $booking->startDate->diffInDays($booking->endDate) }}</td>
                    <td>{{ number_format(($booking->pricing['basePrice'] ?? 0) / $booking->startDate->diffInDays($booking->endDate)) }}</td>
                    <td>{{ number_format($booking->pricing['basePrice'] ?? 0) }}</td>
                </tr>
                
                @if(isset($booking->addOns) && is_array($booking->addOns))
                    @foreach($booking->addOns as $addOn)
                    <tr>
                        <td class="description">{{ $addOn['name'] ?? 'Add-on' }}</td>
                        <td>-</td>
                        <td>-</td>
                        <td>{{ number_format($addOn['price'] ?? 0) }}</td>
                    </tr>
                    @endforeach
                @endif
                
                @if(isset($booking->pricing['tax']) && $booking->pricing['tax'] > 0)
                <tr>
                    <td class="description">Tax</td>
                    <td>-</td>
                    <td>-</td>
                    <td>{{ number_format($booking->pricing['tax']) }}</td>
                </tr>
                @endif
                
                <tr class="total-row">
                    <td class="description"><strong>TOTAL</strong></td>
                    <td><strong>-</strong></td>
                    <td><strong>-</strong></td>
                    <td><strong>{{ number_format($booking->pricing['total'] ?? 0) }}</strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($booking->payment_status === 'completed')
    <div class="payment-info">
        <h3>Payment Information</h3>
        <div class="info-item"><strong>Payment Method:</strong> {{ ucfirst(str_replace('_', ' ', $booking->payment_method)) }}</div>
        <div class="info-item"><strong>Payment Date:</strong> {{ isset($booking->payment['completed_at']) ? \Carbon\Carbon::parse($booking->payment['completed_at'])->format('M j, Y \a\t g:i A') : 'N/A' }}</div>
        @if(isset($booking->payment['transaction_id']))
        <div class="info-item"><strong>Transaction ID:</strong> {{ $booking->payment['transaction_id'] }}</div>
        @endif
        <div class="info-item"><strong>Amount Paid:</strong> UGX {{ number_format($booking->pricing['total'] ?? 0) }}</div>
    </div>
    @endif

    <div class="footer">
        <p><strong>Thank you for choosing MAM Tours!</strong></p>
        <p>For any questions about this invoice, please contact us at +256 755-943973 or info@mamtours.com</p>
        <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
</body>
</html>