<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MAM TOURS AND TRAVEL AGENCY')</title>
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Stripe JS (loaded conditionally) -->
    @if(request()->routeIs('payments.*'))
    <script src="https://js.stripe.com/v3/"></script>
    @endif
    
    @yield('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <div class="logo-container">
                    <img src="{{ asset('images/MAM TOURS LOGO.jpg') }}" alt="MAM TOURS AND TRAVEL AGENCY logo" class="logo-img">
                    <div class="logo-overlay">
                        <i class="fas fa-car logo-icon"></i>
                    </div>
                </div>
                <div class="logo-text">
                    <h1>MAM TOURS</h1>
                    <span class="logo-subtitle">Car Rental</span>
                </div>
            </div>
            <ul class="nav-menu">
                <li><a href="{{ url('/') }}" class="nav-link {{ request()->is('/') || request()->is('home') ? 'active' : '' }}">Home</a></li>
                <li><a href="{{ url('/about') }}" class="nav-link {{ request()->is('about') ? 'active' : '' }}">About</a></li>
                
                @auth
                    @if (Auth::user()->isAdmin())
                        <!-- Admin Navigation -->
                        <li><a href="{{ url('/admin') }}" class="nav-link {{ request()->is('admin') ? 'active' : '' }}">Dashboard</a></li>
                        <li><a href="{{ url('/admin/kyc') }}" class="nav-link {{ request()->is('admin/kyc') ? 'active' : '' }}">KYC Verification</a></li>
                    @else
                        <!-- Customer Navigation -->
                        <li><a href="{{ url('/bookings') }}" class="nav-link {{ request()->is('bookings') ? 'active' : '' }}">Book a car</a></li>
                        <li><a href="{{ url('/dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">My Bookings</a></li>
                    @endif
                @else
                    <!-- Guest Navigation -->
                    <li><a href="{{ url('/bookings') }}" class="nav-link {{ request()->is('bookings') ? 'active' : '' }}">Book a car</a></li>
                @endauth
                
                <li><a href="{{ url('/contact') }}" class="nav-link {{ request()->is('contact') ? 'active' : '' }}">Contact</a></li>
                
                @auth
                    <li class="nav-user">
                        <span class="nav-username">{{ Auth::user()->name }}</span>
                        <a href="{{ url('/profile') }}" class="nav-link">Profile</a>
                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="nav-link logout-link">Logout</button>
                        </form>
                    </li>
                @else
                    <li class="nav-auth">
                        <a href="{{ route('login') }}" class="nav-link">Login</a>
                        <a href="{{ route('register') }}" class="nav-link register-link">Register</a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>

    @yield('content')

    @yield('scripts')
</body>
</html>