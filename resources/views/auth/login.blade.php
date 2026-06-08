@extends('layouts.auth')
@section('body_class', 'auth-login-page')
@section('content')

<div class="auth-login-split">
    <aside class="auth-login-left-panel">
        <div class="auth-login-left-glow" aria-hidden="true"></div>
        <div class="auth-login-left-content">
            <h1 class="auth-login-brand">Mercatoria</h1>
            <p class="auth-login-tagline">Secure marketplace infrastructure.</p>
            <ul class="auth-login-security-features">
                <li>End-to-end encrypted</li>
                <li>PGP supported</li>
                <li>Session integrity verified</li>
            </ul>
        </div>
    </aside>

    <section class="auth-login-right-panel">
        <div class="auth-login-container">
            <div class="auth-login-inner">
                <form action="{{ route('login') }}" method="POST" class="auth-login-form">
                    @csrf
                    <div class="auth-login-form-group">
                        <label for="username" class="auth-login-label">Username</label>
                        <input type="text" id="username" name="username" value="{{ old('username') }}"
                               class="auth-login-input" required minlength="4" maxlength="16">
                    </div>
                    <div class="auth-login-form-group">
                        <label for="password" class="auth-login-label">Password</label>
                        <input type="password" id="password" name="password"
                               class="auth-login-input" required minlength="8" maxlength="40">
                    </div>
                    <div class="auth-login-form-group auth-login-form-group-captcha">
                        <div class="auth-login-captcha-wrapper">
                            <div class="auth-login-captcha-label">CAPTCHA</div>
                            <img src="{{ new Mobicms\Captcha\Image($captchaCode) }}" alt="CAPTCHA Image" class="auth-login-captcha-image">
                            <input type="text" id="captcha" name="captcha" class="auth-login-input" required minlength="2" maxlength="8" placeholder="Enter CAPTCHA">
                        </div>
                    </div>
                    <button type="submit" class="auth-login-submit-btn">Sign In</button>
                </form>
                <div class="auth-login-links">
                    <a href="{{ route('register') }}">Create an Account</a>
                    <span class="auth-login-links-separator">|</span>
                    <a href="{{ route('password.request') }}">Forgot Password</a>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
