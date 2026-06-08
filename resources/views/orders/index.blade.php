@extends('layouts.app')

@section('content')
@php
    $baseQuery = array_filter([
        'sort' => $sortFilter ?? 'newest',
        'from' => $dateFrom ?? null,
        'to' => $dateTo ?? null,
        'per_page' => $perPage ?? 20,
    ], fn ($value) => $value !== null && $value !== '');

    $statusTabs = [
        'all' => 'All',
        'completed' => 'Completed',
        'waiting' => 'Waiting',
        'cancelled' => 'Cancelled',
    ];
@endphp

<div class="orders-index-container">
    <div class="orders-index-card">
        <header class="orders-index-header">
            <div>
                <h1 class="orders-index-title">My Orders</h1>
                <p class="orders-index-subtitle">Track and manage your marketplace transactions</p>
            </div>
        </header>

        <section class="orders-index-filter-bar">
            <nav class="orders-index-status-tabs" aria-label="Order status filters">
                @foreach($statusTabs as $tabKey => $tabLabel)
                    <a
                        href="{{ route('orders.index', array_merge($baseQuery, ['status' => $tabKey])) }}"
                        class="orders-index-status-tab {{ ($statusFilter ?? 'all') === $tabKey ? 'is-active' : '' }}"
                    >
                        {{ $tabLabel }}
                    </a>
                @endforeach
            </nav>
            <form action="{{ route('orders.index') }}" method="GET" class="orders-index-controls">
                <input type="hidden" name="status" value="{{ $statusFilter ?? 'all' }}">
                <div class="orders-index-controls-grid">
                    <div class="orders-index-control-group">
                        <label for="orders-from">From</label>
                        <input id="orders-from" type="date" name="from" value="{{ $dateFrom ?? '' }}">
                    </div>
                    <div class="orders-index-control-group">
                        <label for="orders-to">To</label>
                        <input id="orders-to" type="date" name="to" value="{{ $dateTo ?? '' }}">
                    </div>
                    <div class="orders-index-control-group">
                        <label for="orders-sort">Sort By</label>
                        <select id="orders-sort" name="sort">
                            <option value="newest" {{ ($sortFilter ?? 'newest') === 'newest' ? 'selected' : '' }}>Newest</option>
                            <option value="oldest" {{ ($sortFilter ?? '') === 'oldest' ? 'selected' : '' }}>Oldest</option>
                            <option value="highest_amount" {{ ($sortFilter ?? '') === 'highest_amount' ? 'selected' : '' }}>Highest Amount</option>
                            <option value="lowest_amount" {{ ($sortFilter ?? '') === 'lowest_amount' ? 'selected' : '' }}>Lowest Amount</option>
                        </select>
                    </div>
                    <div class="orders-index-control-group">
                        <label for="orders-per-page">Rows</label>
                        <select id="orders-per-page" name="per_page">
                            @foreach([10, 20, 50] as $size)
                                <option value="{{ $size }}" {{ (int) ($perPage ?? 20) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit" class="orders-index-control-btn">Apply</button>
            </form>
        </section>

        {{-- Orders List --}}
        <section class="orders-index-section">
            @if($orders->isEmpty())
                <div class="orders-index-empty">
                    <p>You don't have any orders yet.</p>
                    <a href="{{ route('products.index') }}" class="orders-index-browse-btn">Browse Products</a>
                </div>
            @else
                <div class="orders-index-list">
                    <div class="orders-index-list-head" aria-hidden="true">
                        <span>Order ID</span>
                        <span>Date</span>
                        <span>Vendor</span>
                        <span>Total</span>
                        <span>Status</span>
                        <span>Actions</span>
                    </div>

                    @foreach($orders as $order)
                        <article class="orders-index-row orders-index-row-status-{{ $order->status }} {{ in_array($order->status, ['waiting_payment', 'payment_received', 'product_sent', 'disputed']) ? 'orders-index-row-active' : '' }}">
                            <div class="orders-index-cell">
                                <span class="orders-index-mobile-label">Order ID</span>
                                <span class="orders-index-value orders-index-id-text" title="{{ $order->id }}">{{ $order->id }}</span>
                            </div>
                            <div class="orders-index-cell">
                                <span class="orders-index-mobile-label">Date</span>
                                <span class="orders-index-value orders-index-secondary">{{ $order->created_at->format('Y-m-d / H:i') }}</span>
                            </div>
                            <div class="orders-index-cell">
                                <span class="orders-index-mobile-label">Vendor</span>
                                <span class="orders-index-value">{{ $order->vendor->username }}</span>
                            </div>
                            <div class="orders-index-cell">
                                <span class="orders-index-mobile-label">Total</span>
                                <span class="orders-index-value orders-index-total">${{ number_format($order->total, 2) }}</span>
                            </div>
                            <div class="orders-index-cell">
                                <span class="orders-index-mobile-label">Status</span>
                                <span class="orders-index-status orders-index-status-{{ strtolower($order->status) }}">
                                    {{ $order->getFormattedStatus() }}
                                </span>
                            </div>
                            <div class="orders-index-cell orders-index-cell-action">
                                <a href="{{ route('orders.show', $order->unique_url) }}" class="orders-index-action-btn">
                                    View Details
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="orders-index-pagination-bar">
                    <div class="orders-index-pagination-meta">
                        <span>Total Orders: {{ number_format($orders->total()) }}</span>
                        <span>Showing {{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }}</span>
                    </div>
                    <div class="orders-index-pagination-container">
                        {{ $orders->onEachSide(1)->links() }}
                    </div>
                    <div class="orders-index-pagination-spacer" aria-hidden="true"></div>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
