@extends('layouts.app')

@section('title', 'Dashboard | MAM TOURS AND TRAVEL AGENCY')

@section('content')
    <section class="page-hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Welcome, {{ Auth::user()->name }}!</h1>
            <p class="hero-subtitle">{{ Auth::user()->isAdmin() ? 'Admin Dashboard' : 'Your Dashboard' }}</p>
        </div>
    </section>

    <section class="content-section">
        <div class="container">
            <div class="dashboard-wrapper">
                @if (Auth::user()->isAdmin())
                    <!-- Admin Dashboard -->
                    <div class="dashboard-header">
                        <h2><i class="fas fa-chart-line"></i> Admin Dashboard</h2>
                        <a href="{{ url('/admin') }}" class="cta-button">Go to Admin Panel</a>
                    </div>
                    <div class="dashboard-grid">
                        <div class="dashboard-card">
                            <h3><i class="fas fa-car"></i> Total Vehicles</h3>
                            <p class="dashboard-stat">{{ $totalCars ?? 0 }}</p>
                        </div>
                        <div class="dashboard-card">
                            <h3><i class="fas fa-calendar-alt"></i> Active Bookings</h3>
                            <p class="dashboard-stat">{{ $activeBookings ?? 0 }}</p>
                        </div>
                        <div class="dashboard-card">
                            <h3><i class="fas fa-check-circle"></i> Completed Bookings</h3>
                            <p class="dashboard-stat">{{ $completedBookings ?? 0 }}</p>
                        </div>
                        <div class="dashboard-card">
                            <h3><i class="fas fa-bolt"></i> Available Vehicles</h3>
                            <p class="dashboard-stat">{{ $availableCars ?? 0 }}</p>
                        </div>
                    </div>
                @else
                    <!-- User Dashboard -->
                    <div class="dashboard-header">
                        <h2><i class="fas fa-list"></i> Your Bookings</h2>
                        <a href="{{ url('/bookings') }}" class="cta-button">Book a Vehicle</a>
                    </div>
                    <div class="bookings-list">
                        @if ($userBookings && count($userBookings) > 0)
                            @foreach ($userBookings as $booking)
                                <div class="booking-card">
                                    <div class="booking-header">
                                        <h3><i class="fas fa-car"></i> {{ $booking->car->brand }} {{ $booking->car->model }}</h3>
                                        <span class="booking-status">{{ ucfirst($booking->status) }}</span>
                                    </div>
                                    <div class="booking-details">
                                        <p><strong><i class="fas fa-calendar-alt"></i> Dates:</strong> {{ $booking->startDate }} to {{ $booking->endDate }}</p>
                                        <p><strong><i class="fas fa-money-bill-wave"></i> Total:</strong> UGX {{ number_format($booking->pricing['total'] ?? 0) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p>You haven't made any bookings yet. <a href="{{ url('/bookings') }}"><i class="fas fa-car"></i> Book a vehicle now!</a></p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>

    <style>
        .dashboard-wrapper {
            padding: 40px 0;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .dashboard-header h2 {
            font-size: 32px;
            color: #333;
            margin: 0;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .dashboard-card h3 {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            margin: 0 0 15px 0;
            letter-spacing: 1px;
        }
        .dashboard-stat {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin: 0;
        }
        .bookings-list {
            display: grid;
            gap: 20px;
        }
        .booking-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .booking-header h3 {
            margin: 0;
            color: #333;
        }
        .booking-status {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .booking-details p {
            margin: 8px 0;
            color: #666;
        }
    </style>
@endsection
