@extends('layouts.app')
@section('content')

<div class="dashboard-ops">
    <header class="dashboard-ops-header">
        <div class="dashboard-ops-header-main">
            <h1 class="dashboard-ops-title">{{ e($user->username) }} Control Interface</h1>
            <p class="dashboard-ops-subtitle">
                @if($showFullInfo)
                    Operational overview of account activity and marketplace posture.
                @else
                    Public profile overview.
                @endif
            </p>
        </div>
        <div class="dashboard-ops-header-badges">
            <span class="dashboard-ops-badge dashboard-ops-badge-{{ $systemStatus }}">
                {{ $systemStatusText }}
            </span>
            @if($showFullInfo)
                <span class="dashboard-ops-badge dashboard-ops-badge-live">
                    Live activity {{ $liveActivityCount }}
                </span>
            @endif
        </div>
    </header>

    @if($showFullInfo)
        <section class="dashboard-ops-metrics">
            @foreach($metrics as $metric)
                <article class="dashboard-ops-metric-card dashboard-ops-metric-card-{{ $metric['status'] }}">
                    <p class="dashboard-ops-metric-label">{{ $metric['label'] }}</p>
                    <p class="dashboard-ops-metric-value">{{ $metric['value'] }}</p>
                    <p class="dashboard-ops-metric-context">{{ $metric['context'] }}</p>
                    <p class="dashboard-ops-metric-trend dashboard-ops-metric-trend-{{ $metric['trend']['direction'] }}">
                        {{ $metric['trend']['text'] }}
                    </p>
                </article>
            @endforeach
        </section>
    @endif

    <section class="dashboard-ops-main-grid">
        @if($showFullInfo)
            <article class="dashboard-ops-panel dashboard-ops-panel-primary">
                <div class="dashboard-ops-panel-head">
                    <h2 class="dashboard-ops-panel-title">Revenue Trend</h2>
                    <span class="dashboard-ops-panel-meta">Last 7 days</span>
                </div>

                <div class="dashboard-ops-chart-shell">
                    <svg class="dashboard-ops-chart" viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Revenue line chart">
                        <polyline points="{{ $chartPoints }}" />
                    </svg>
                    <div class="dashboard-ops-chart-labels">
                        @foreach($chartSeries as $point)
                            <span>{{ $point['label'] }}</span>
                        @endforeach
                    </div>
                </div>
            </article>
        @endif

        <aside class="dashboard-ops-panel dashboard-ops-panel-side">
            <div class="dashboard-ops-panel-head">
                <h2 class="dashboard-ops-panel-title">Account Snapshot</h2>
            </div>
            <div class="dashboard-ops-profile">
                <div class="dashboard-ops-profile-image-wrap">
                    <img class="dashboard-ops-profile-image" src="{{ $profile ? $profile->profile_picture_url : asset('images/default-profile-picture.png') }}" alt="Profile Picture">
                </div>
                <div class="dashboard-ops-profile-meta">
                    <p class="dashboard-ops-profile-role">{{ $userRole }}</p>
                    @if($showFullInfo)
                        <p class="dashboard-ops-profile-inline">Wishlist: {{ number_format($wishlistCount) }}</p>
                    @endif
                    @if($showFullInfo)
                        <p class="dashboard-ops-profile-inline">Last Login: {{ $user->last_login ? $user->last_login->format('d-m-Y') : 'Never' }}</p>
                    @endif
                </div>
            </div>
            <div class="dashboard-ops-pgp">
                <span class="dashboard-ops-pgp-label">PGP Key</span>
                <span class="dashboard-ops-pgp-status {{ ($pgpKey && $pgpKey->verified) ? 'verified' : 'unverified' }}">
                    {{ ($pgpKey && $pgpKey->verified) ? 'Verified' : 'Unverified' }}
                </span>
            </div>
        </aside>
    </section>

    <section class="dashboard-ops-lower-grid">
        @if($showFullInfo)
            <article class="dashboard-ops-panel dashboard-ops-panel-activity">
                <div class="dashboard-ops-panel-head">
                    <h2 class="dashboard-ops-panel-title">Recent Activity</h2>
                    <span class="dashboard-ops-panel-meta">Latest 10 events</span>
                </div>
                <ul class="dashboard-ops-activity-list">
                    @forelse($recentEvents as $event)
                        <li class="dashboard-ops-activity-row">
                            <span class="dashboard-ops-activity-type">{{ $event['type'] }}</span>
                            <span class="dashboard-ops-activity-main">{{ $event['title'] }}</span>
                            <span class="dashboard-ops-activity-meta">{{ $event['meta'] }}</span>
                            <span class="dashboard-ops-activity-time">{{ $event['created_at']->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="dashboard-ops-activity-empty">No recent activity recorded.</li>
                    @endforelse
                </ul>
            </article>
        @endif

        <article class="dashboard-ops-panel dashboard-ops-panel-context">
            <div class="dashboard-ops-panel-head">
                <h2 class="dashboard-ops-panel-title">Profile Context</h2>
            </div>
            <div class="dashboard-ops-context-block">
                <h3 class="dashboard-ops-context-title">Description</h3>
                <p class="dashboard-ops-context-text">{{ $description }}</p>
            </div>
            <div class="dashboard-ops-context-block">
                <h3 class="dashboard-ops-context-title">Current PGP Key</h3>
                <div class="dashboard-ops-context-pre">
                    @if($pgpKey && $showFullInfo)
                        <pre>{{ $pgpKey->public_key }}</pre>
                    @elseif($pgpKey && !$showFullInfo)
                        <p>PGP key is configured.</p>
                    @else
                        <p>No PGP key added yet.</p>
                    @endif
                </div>
            </div>
        </article>
    </section>
</div>
@endsection
