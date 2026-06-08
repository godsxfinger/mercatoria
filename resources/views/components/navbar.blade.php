<nav class="navbar">
    <div class="navbar-container">
        <div class="navbar-content">
            <a href="{{ route('home') }}" class="navbar-wordmark">
                <span class="navbar-wordmark-dot"></span>
                <span class="navbar-wordmark-text">Mercatoria</span>
            </a>

            @php
                $xmrPrice = app(App\Http\Controllers\XmrPriceController::class)->getXmrPrice();
            @endphp
            <div class="navbar-xmr-price" aria-live="polite">
                <span class="navbar-xmr-price-label">XMR/USD:</span>
                <span class="navbar-xmr-price-value {{ $xmrPrice === 'UNAVAILABLE' ? 'unavailable' : '' }}">
                    @if($xmrPrice !== 'UNAVAILABLE')
                        ${{ $xmrPrice }}
                    @else
                        {{ $xmrPrice }}
                    @endif
                </span>
            </div>

            @auth
                <div class="navbar-right">
                    <a href="{{ route('cart.index') }}" class="navbar-icon-btn {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/cart.png') }}" alt="Cart" class="navbar-icon-png">
                        @if(auth()->user()->cartItems()->count() > 0)
                            <span class="navbar-badge navbar-badge-cart">{{ auth()->user()->cartItems()->count() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('notifications.index') }}" class="navbar-icon-btn {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                        <img src="{{ asset('icons/notifications.png') }}" alt="Notifications" class="navbar-icon-png">
                        @if(auth()->user()->unread_notifications_count > 0)
                            <span class="navbar-badge navbar-badge-notification">{{ auth()->user()->unread_notifications_count }}</span>
                        @endif
                    </a>

                    <details class="navbar-profile-menu">
                        <summary class="navbar-profile-trigger" aria-label="Profile menu">
                            <span class="navbar-profile-avatar">
                                {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                            </span>
                        </summary>
                        <div class="navbar-profile-dropdown">
                            <div class="navbar-profile-header">
                                <span class="navbar-profile-header-avatar">
                                    {{ strtoupper(substr(auth()->user()->username, 0, 1)) }}
                                </span>
                                <div class="navbar-profile-header-meta">
                                    <p class="navbar-profile-header-name">{{ auth()->user()->name ?: auth()->user()->username }}</p>
                                    <p class="navbar-profile-header-email">{{ auth()->user()->email }}</p>
                                </div>
                            </div>

                            <div class="navbar-profile-group">
                                <p class="navbar-profile-group-title">Account</p>
                                <a href="{{ route('dashboard') }}" class="navbar-profile-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M3 11.5L12 4l9 7.5" /><path d="M5 10.5V20h14v-9.5" /></svg>
                                    </span>
                                    Dashboard
                                </a>
                                <a href="{{ route('messages.index') }}" class="navbar-profile-link {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v10H7l-3 3V6z" /></svg>
                                    </span>
                                    Messages
                                </a>
                            </div>

                            <div class="navbar-profile-group">
                                <p class="navbar-profile-group-title">Preferences</p>
                                <a href="{{ route('settings') }}" class="navbar-profile-link {{ request()->routeIs('settings') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z" /><path d="M19.4 15a1 1 0 0 0 .2 1.1l.1.1a1.2 1.2 0 0 1 0 1.7l-1.6 1.6a1.2 1.2 0 0 1-1.7 0l-.1-.1a1 1 0 0 0-1.1-.2 1 1 0 0 0-.6.9V20a1.2 1.2 0 0 1-1.2 1.2h-2.2A1.2 1.2 0 0 1 10 20v-.1a1 1 0 0 0-.6-.9 1 1 0 0 0-1.1.2l-.1.1a1.2 1.2 0 0 1-1.7 0l-1.6-1.6a1.2 1.2 0 0 1 0-1.7l.1-.1a1 1 0 0 0 .2-1.1 1 1 0 0 0-.9-.6H4A1.2 1.2 0 0 1 2.8 13v-2.2A1.2 1.2 0 0 1 4 9.6h.1a1 1 0 0 0 .9-.6 1 1 0 0 0-.2-1.1l-.1-.1a1.2 1.2 0 0 1 0-1.7l1.6-1.6a1.2 1.2 0 0 1 1.7 0l.1.1a1 1 0 0 0 1.1.2A1 1 0 0 0 10 3.9V3.8A1.2 1.2 0 0 1 11.2 2.6h2.2A1.2 1.2 0 0 1 14.6 3.8v.1a1 1 0 0 0 .6.9 1 1 0 0 0 1.1-.2l.1-.1a1.2 1.2 0 0 1 1.7 0l1.6 1.6a1.2 1.2 0 0 1 0 1.7l-.1.1a1 1 0 0 0-.2 1.1 1 1 0 0 0 .9.6h.1a1.2 1.2 0 0 1 1.2 1.2V13a1.2 1.2 0 0 1-1.2 1.2h-.1a1 1 0 0 0-.9.8z" /></svg>
                                    </span>
                                    Settings
                                </a>
                                <a href="{{ route('profile') }}" class="navbar-profile-link {{ request()->routeIs('profile') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" /><path d="M5 19c1.5-2.5 4-4 7-4s5.5 1.5 7 4" /></svg>
                                    </span>
                                    Account
                                </a>
                            </div>

                            <div class="navbar-profile-group">
                                <p class="navbar-profile-group-title">Support</p>
                                <a href="{{ route('support.index') }}" class="navbar-profile-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 18v-5a6 6 0 0 1 12 0v5" /><path d="M6 16H4v-3h2m12 3h2v-3h-2" /><path d="M10 20h4" /></svg>
                                    </span>
                                    Support
                                </a>
                                <a href="{{ route('guides.index') }}" class="navbar-profile-link {{ request()->routeIs('guides.*') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5h7a3 3 0 0 1 3 3V19H7a3 3 0 0 0-3 3V5.5z" /><path d="M20 5.5h-7a3 3 0 0 0-3 3V19h7a3 3 0 0 1 3 3V5.5z" /></svg>
                                    </span>
                                    Guides
                                </a>
                                <a href="{{ route('rules') }}" class="navbar-profile-link {{ request()->routeIs('rules') ? 'active' : '' }}">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M6 4h10l2 2v14H6z" /><path d="M16 4v3h3" /><path d="M9 12h6M9 16h6" /></svg>
                                    </span>
                                    Rules
                                </a>
                            </div>

                            <form action="{{ route('logout') }}" method="POST" class="navbar-profile-logout-wrap">
                                @csrf
                                <button type="submit" class="navbar-profile-link navbar-profile-logout">
                                    <span class="navbar-profile-link-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 4H5v16h4" /><path d="M16 8l4 4-4 4" /><path d="M20 12H9" /></svg>
                                    </span>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </details>
                </div>
            @endauth
        </div>
    </div>
</nav>
