@extends('layouts.app')

@section('content')
<div class="admin-cc">
    <header class="admin-cc-header">
        <div class="admin-cc-header-main">
            <h1 class="admin-cc-title">Admin Control Center</h1>
            <p class="admin-cc-subtitle">Live operational command interface for {{ config('app.name') }}</p>
        </div>
        <div class="admin-cc-utilities">
            <a href="{{ route('admin.logs') }}" class="admin-cc-utility-link">Logs</a>
            <a href="{{ route('admin.statistics') }}" class="admin-cc-utility-link">Statistics</a>
            <a href="{{ route('admin.canary') }}" class="admin-cc-utility-link">Canary</a>
            <a href="{{ route('admin.popup.index') }}" class="admin-cc-utility-link">Popups</a>
        </div>
    </header>

    <section class="admin-cc-kpi-row">
        <article class="admin-cc-kpi-card">
            <p class="admin-cc-kpi-label">Total Users</p>
            <p class="admin-cc-kpi-value">{{ number_format($kpis['total_users']) }}</p>
            <p class="admin-cc-kpi-trend admin-cc-kpi-trend-{{ $kpiTrends['total_users']['direction'] }}">{{ $kpiTrends['total_users']['text'] }}</p>
        </article>
        <article class="admin-cc-kpi-card">
            <p class="admin-cc-kpi-label">Total Orders</p>
            <p class="admin-cc-kpi-value">{{ number_format($kpis['total_orders']) }}</p>
            <p class="admin-cc-kpi-trend admin-cc-kpi-trend-{{ $kpiTrends['total_orders']['direction'] }}">{{ $kpiTrends['total_orders']['text'] }}</p>
        </article>
        <article class="admin-cc-kpi-card">
            <p class="admin-cc-kpi-label">GMV (Paid Orders)</p>
            <p class="admin-cc-kpi-value">${{ number_format($kpis['gmv_paid'], 2) }}</p>
            <p class="admin-cc-kpi-trend admin-cc-kpi-trend-{{ $kpiTrends['gmv_paid']['direction'] }}">{{ $kpiTrends['gmv_paid']['text'] }}</p>
        </article>
        <article class="admin-cc-kpi-card">
            <p class="admin-cc-kpi-label">Total Marketplace Income</p>
            <p class="admin-cc-kpi-value">${{ number_format($kpis['total_marketplace_income'], 2) }}</p>
            <p class="admin-cc-kpi-trend admin-cc-kpi-trend-{{ $kpiTrends['total_marketplace_income']['direction'] }}">{{ $kpiTrends['total_marketplace_income']['text'] }}</p>
        </article>
    </section>

    <section class="admin-cc-operations">
        <article class="admin-cc-panel admin-cc-panel-feed">
            <div class="admin-cc-panel-head">
                <h2 class="admin-cc-panel-title">Admin Modules</h2>
                <span class="admin-cc-panel-meta">Core management areas</span>
            </div>
            <div class="admin-cc-modules admin-cc-modules-swapped">
                <article class="admin-cc-module-card">
                    <h3 class="admin-cc-module-title">User Management</h3>
                    <div class="admin-cc-module-list">
                        <a href="{{ route('admin.users') }}" class="admin-cc-module-row">Users</a>
                        <a href="{{ route('admin.users', ['role' => 'vendor']) }}" class="admin-cc-module-row">Vendors</a>
                        <a href="{{ route('admin.vendor-applications.index') }}" class="admin-cc-module-row">Vendor Applications</a>
                    </div>
                </article>

                <article class="admin-cc-module-card">
                    <h3 class="admin-cc-module-title">Moderation</h3>
                    <div class="admin-cc-module-list">
                        <a href="{{ route('admin.disputes.index') }}" class="admin-cc-module-row">Disputes</a>
                        <a href="{{ route('admin.support.requests') }}" class="admin-cc-module-row">Support Requests</a>
                        <a href="{{ route('admin.bulk-message.list') }}" class="admin-cc-module-row">Bulk Messaging</a>
                    </div>
                </article>

                <article class="admin-cc-module-card">
                    <h3 class="admin-cc-module-title">System</h3>
                    <div class="admin-cc-module-list">
                        <a href="{{ route('admin.categories') }}" class="admin-cc-module-row">Categories</a>
                        <a href="{{ route('admin.logs') }}" class="admin-cc-module-row">System Logs</a>
                        <a href="{{ route('admin.canary') }}" class="admin-cc-module-row">Canary</a>
                    </div>
                </article>

                <article class="admin-cc-module-card">
                    <h3 class="admin-cc-module-title">Analytics</h3>
                    <div class="admin-cc-module-list">
                        <a href="{{ route('admin.statistics') }}" class="admin-cc-module-row">Statistics</a>
                        <a href="{{ route('admin.all-products') }}" class="admin-cc-module-row">Products Overview</a>
                        <a href="{{ route('admin.popup.index') }}" class="admin-cc-module-row">Popup Performance</a>
                    </div>
                </article>
            </div>
            <div class="admin-cc-panel-head admin-cc-panel-head-inline-feed">
                <h3 class="admin-cc-module-title">Live Activity Feed</h3>
                <span class="admin-cc-panel-meta">Latest 14 events</span>
            </div>
            <ul class="admin-cc-feed-list admin-cc-feed-list-inline">
                @forelse($liveFeed as $item)
                    <li class="admin-cc-feed-row">
                        <span class="admin-cc-feed-type">{{ $item['type'] }}</span>
                        @if($item['url'])
                            <a href="{{ $item['url'] }}" class="admin-cc-feed-main">{{ $item['title'] }}</a>
                        @else
                            <span class="admin-cc-feed-main">{{ $item['title'] }}</span>
                        @endif
                        <span class="admin-cc-feed-meta">{{ $item['meta'] }}</span>
                        <time class="admin-cc-feed-time">{{ $item['timestamp']->diffForHumans() }}</time>
                    </li>
                @empty
                    <li class="admin-cc-feed-empty">No recent activity.</li>
                @endforelse
            </ul>
        </article>

        <aside class="admin-cc-panel admin-cc-panel-controls">
            <div class="admin-cc-panel-head">
                <h2 class="admin-cc-panel-title">Quick Controls</h2>
                <span class="admin-cc-panel-meta">Operational shortcuts</span>
            </div>
            <div class="admin-cc-control-metrics">
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Pending Support</span>
                    <strong class="admin-cc-control-value">{{ number_format($quickControls['pending_support']) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Active Disputes</span>
                    <strong class="admin-cc-control-value">{{ number_format($quickControls['active_disputes']) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Vendor Applications</span>
                    <strong class="admin-cc-control-value">{{ number_format($quickControls['pending_vendor_applications']) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Active Popups</span>
                    <strong class="admin-cc-control-value">{{ number_format($quickControls['active_popups']) }}</strong>
                </div>
            </div>
            <div class="admin-cc-control-links">
                <a href="{{ route('admin.support.requests') }}" class="admin-cc-control-link">Open Support Queue</a>
                <a href="{{ route('admin.disputes.index') }}" class="admin-cc-control-link">Review Disputes</a>
                <a href="{{ route('admin.vendor-applications.index') }}" class="admin-cc-control-link">Process Vendor Applications</a>
                <a href="{{ route('admin.bulk-message.list') }}" class="admin-cc-control-link">Send Bulk Message</a>
            </div>

            <div class="admin-cc-panel-head u-mt-md">
                <h2 class="admin-cc-panel-title">Financial Snapshot</h2>
                <span class="admin-cc-panel-meta">Realized cashflow</span>
            </div>
            <div class="admin-cc-control-metrics">
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Order Net Fees</span>
                    <strong class="admin-cc-control-value">${{ number_format($kpis['order_net_fees'], 2) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Ad Revenue</span>
                    <strong class="admin-cc-control-value">${{ number_format($kpis['ad_revenue_collected'], 2) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Vendor App Net Fees</span>
                    <strong class="admin-cc-control-value">${{ number_format($kpis['vendor_app_net_fees'], 2) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Vendor Payouts</span>
                    <strong class="admin-cc-control-value">${{ number_format($kpis['vendor_payouts'], 2) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Buyer Refunds</span>
                    <strong class="admin-cc-control-value">${{ number_format($kpis['buyer_refunds'], 2) }}</strong>
                </div>
                <div class="admin-cc-control-metric">
                    <span class="admin-cc-control-label">Open Disputes</span>
                    <strong class="admin-cc-control-value">{{ number_format($kpis['open_disputes']) }}</strong>
                </div>
            </div>
        </aside>
    </section>

</div>
@endsection
