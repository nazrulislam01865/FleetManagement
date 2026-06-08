@extends('layouts.auth')

@section('title', 'Login')

@section('auth-content')
    <div class="login-card-head">
        <span>Secure Access</span>
        <h2>Sign in to FleetMan</h2>
        <p>Use your account to open the dashboard.</p>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="login-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        @php
            $statusMessage = (string) session('status');
            $isSessionExpired = str_contains(strtolower($statusMessage), 'session expired');
        @endphp
        <div class="{{ $isSessionExpired ? 'login-error' : 'login-success' }}" role="{{ $isSessionExpired ? 'alert' : 'status' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.store') }}" class="login-form">
        @csrf
        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">Password <span class="req">*</span></label>
            <input id="password" name="password" type="password" autocomplete="current-password" required placeholder="Enter password">
        </div>

        <label class="remember-line">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            <span>Keep me signed in</span>
        </label>

        <div class="login-action-grid">
            <button class="btn primary login-submit" type="submit">Login to Dashboard</button>
            <a class="btn light forgot-password-btn" href="{{ route('password.request') }}">Forgot Password?</a>
        </div>
    </form>
@endsection
