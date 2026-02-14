<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard | MAM Tours & Travel')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @yield('styles')
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="{{ url('/') }}" class="logo">
                <div class="logo-text">
                    <h1>MAM TOURS</h1>
                    <span class="logo-subtitle">Admin</span>
                </div>
            </a>
            <ul class="nav-menu">
                <li><a href="{{ url('/') }}" class="nav-link"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="{{ url('/admin') }}" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="{{ url('/profile') }}" class="nav-link"><i class="fas fa-user-circle"></i> Profile</a></li>
            </ul>
            <div class="nav-user">
                <span class="nav-username">{{ auth()->user()->name }}</span>
                <button class="logout-link" onclick="document.getElementById('logoutForm').submit();"><i class="fas fa-sign-out-alt"></i> Logout</button>
                <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main-content">
        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>