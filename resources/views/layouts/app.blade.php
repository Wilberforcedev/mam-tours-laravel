<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fbf8f2">
    <title>@yield('title', 'MAM Tours | Travel & Experiences')</title>
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @if(request()->routeIs('payments.*'))
    <script src="https://js.stripe.com/v3/"></script>
    @endif
    @yield('styles')
</head>
<body class="@yield('body_class')">
    <nav class="navbar">
        <div class="nav-container">
            <a class="logo" href="{{ url('/') }}" aria-label="MAM Tours home">
                <div class="logo-container">
                    <img src="{{ asset('images/MAM TOURS LOGO.jpg') }}" alt="MAM Tours logo" class="logo-img">
                    <div class="logo-overlay"><i class="fas fa-compass logo-icon"></i></div>
                </div>
                <div class="logo-text">
                    <h1>MAM TOURS</h1>
                    <span class="logo-subtitle">Travel & Experiences</span>
                </div>
            </a>
            <ul class="nav-menu">
                <li><a href="{{ url('/#journeys') }}" class="nav-link">Journeys</a></li>
                <li><a href="{{ url('/explore-uganda') }}" class="nav-link {{ request()->is('explore-uganda') ? 'active' : '' }}">Destinations</a></li>
                <li><a href="{{ url('/about') }}" class="nav-link {{ request()->is('about') ? 'active' : '' }}">Our story</a></li>
                @auth
                    @if (Auth::user()->isAdmin())
                        <li><a href="{{ url('/admin') }}" class="nav-link {{ request()->is('admin') ? 'active' : '' }}">Dashboard</a></li>
                        <li><a href="{{ url('/admin/kyc') }}" class="nav-link {{ request()->is('admin/kyc') ? 'active' : '' }}">KYC Verification</a></li>
                    @else
                        <li><a href="{{ url('/bookings') }}" class="nav-link {{ request()->is('bookings') ? 'active' : '' }}">Book a car</a></li>
                        <li><a href="{{ url('/dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">My Bookings</a></li>
                    @endif
                @else
                    <li><a href="{{ url('/bookings') }}" class="nav-link {{ request()->is('bookings') ? 'active' : '' }}">Car hire</a></li>
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

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .catch(() => {});
            });
        }
    </script>
    @yield('scripts')
</body>
</html>
