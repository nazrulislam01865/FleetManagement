@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('auth-content')
    <div class="login-card-head">
        <span>Password Recovery</span>
        <h2>Forgot your password?</h2>
        <p>Enter your account email address and we will send you a secure password reset link.</p>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="login-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    @if (session('status'))
        <div class="login-success" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="login-form">
        @csrf
        <div class="field">
            <label for="email">Email Address <span class="req">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        </div>

        <button class="btn primary login-submit" type="submit">Email Password Reset Link</button>
        <a class="auth-back-link" href="{{ route('login') }}">Back to sign in</a>
    </form>
@endsection
