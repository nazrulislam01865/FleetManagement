@extends('layouts.auth')

@section('title', 'Reset Password')

@section('auth-content')
    <div class="login-card-head">
        <span>Password Recovery</span>
        <h2>Create a new password</h2>
        <p>Choose a strong password for your FleetMan account.</p>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="login-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="login-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus>
        </div>

        <div class="field">
            <label for="password">New Password <span class="req">*</span></label>
            <input id="password" name="password" type="password" autocomplete="new-password" required placeholder="Enter a new password">
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm New Password <span class="req">*</span></label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Re-enter the new password">
        </div>

        <button class="btn primary login-submit" type="submit">Reset Password</button>
        <a class="auth-back-link" href="{{ route('login') }}">Back to sign in</a>
    </form>
@endsection
