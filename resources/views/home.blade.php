@extends('layouts.app')

@section('content')
<div class="market-home">
    <div class="market-home-announcement">
        <span class="market-home-announcement-dot"></span>
        <p>{{ $announcementText }}</p>
    </div>

    <section class="market-home-section">
        <div class="market-home-section-head">
            <h2>Featured Products</h2>
            <a href="{{ route('products.index') }}">View all</a>
        </div>
        <div class="market-home-featured-layout">
            @if(!empty($featuredProducts))
                @php
                    $hero = $featuredProducts[0];
                    $heroSignal = $liveSignals[(string) $hero['product']->id] ?? ['views' => 0, 'sold' => 0, 'badge' => null];
                    $heroPositive = $hero['product']->getPositiveReviewPercentage();
                @endphp
                <article class="market-home-featured-hero">
                    <a href="{{ route('products.show', $hero['product']->slug) }}" class="market-home-featured-hero-image-link">
                        @if(!empty($hero['is_sponsored']))
                            <span class="market-home-sponsored-pill">
                                Sponsored @if(!empty($hero['ad_slot'])) · S{{ $hero['ad_slot'] }} @endif
                            </span>
                            <span class="market-home-promoted-badge">Promoted</span>
                        @endif
                        <img src="{{ $hero['product']->product_picture_url }}" alt="{{ $hero['product']->name }}" class="market-home-featured-hero-image">
                        <span class="market-home-featured-image-overlay" aria-hidden="true"></span>
                    </a>
                    <div class="market-home-featured-hero-body">
                        <p class="market-home-featured-kicker">
                            @if(!empty($hero['is_sponsored']))
                                Sponsored @if(!empty($hero['ad_slot'])) · Slot {{ $hero['ad_slot'] }} @endif
                            @else
                                Featured pick
                            @endif
                        </p>
                        <a href="{{ route('products.show', $hero['product']->slug) }}" class="market-home-featured-hero-title">{{ $hero['product']->name }}</a>
                        <p class="market-home-featured-hero-meta">By {{ $hero['vendor']->username }} · {{ $hero['product']->category->name }}</p>
                        <p class="market-home-featured-hero-signal">
                            👁 <strong>{{ number_format($heroSignal['views']) }}</strong> viewing now
                        </p>
                        @if((int) ($heroSignal['sold'] ?? 0) > 0)
                            <p class="market-home-featured-live-purchase">{{ number_format((int) $heroSignal['sold']) }} recently purchased</p>
                        @endif
                        <div class="market-home-featured-hero-trust">
                            <span>
                                ★
                                @if($heroPositive !== null)
                                    {{ number_format($heroPositive, 0) }}% positive
                                @else
                                    No reviews yet
                                @endif
                            </span>
                            <span>Sold {{ number_format((int) ($heroSignal['sold'] ?? 0)) }}</span>
                            @if(!empty($heroSignal['badge']))
                                <span class="market-home-featured-trending">{{ $heroSignal['badge'] }}</span>
                            @endif
                        </div>
                        <div class="market-home-featured-hero-price">
                            ${{ number_format($hero['product']->price, 2) }}
                            @if($hero['xmr_price'] !== null)
                                <small>≈ {{ number_format($hero['xmr_price'], 4) }} XMR</small>
                            @endif
                        </div>
                        <div class="market-home-featured-hero-cta">
                            <a href="{{ route('products.show', $hero['product']->slug) }}" class="market-home-featured-cta-primary">View Product</a>
                            <form action="{{ route('cart.store', $hero['product']) }}" method="POST">
                                @csrf
                                <button type="submit" class="market-home-featured-cta-secondary">Buy Now</button>
                            </form>
                        </div>
                    </div>
                </article>

                <div class="market-home-featured-side">
                    @foreach(array_slice($featuredProducts, 1, 4) as $featured)
                        @php
                            $miniSignal = $liveSignals[(string) $featured['product']->id] ?? ['views' => 0, 'sold' => 0, 'badge' => null];
                            $miniPositive = $featured['product']->getPositiveReviewPercentage();
                        @endphp
                        <article class="market-home-featured-mini">
                            <a href="{{ route('products.show', $featured['product']->slug) }}" class="market-home-featured-mini-image-link">
                                @if(!empty($featured['is_sponsored']))
                                    <span class="market-home-sponsored-pill market-home-sponsored-pill-mini">Sponsored</span>
                                    <span class="market-home-promoted-badge market-home-promoted-badge-mini">Promoted</span>
                                @endif
                                <img src="{{ $featured['product']->product_picture_url }}" alt="{{ $featured['product']->name }}" class="market-home-featured-mini-image">
                                <span class="market-home-featured-image-overlay" aria-hidden="true"></span>
                            </a>
                            <div class="market-home-featured-mini-body">
                                <a href="{{ route('products.show', $featured['product']->slug) }}" class="market-home-featured-mini-title">{{ $featured['product']->name }}</a>
                                <span class="market-home-featured-mini-views">👁 {{ number_format($miniSignal['views']) }} viewing now</span>
                                <span class="market-home-featured-mini-views">
                                    ★
                                    @if($miniPositive !== null)
                                        {{ number_format($miniPositive, 0) }}% positive
                                    @else
                                        No reviews
                                    @endif
                                </span>
                                @if(!empty($miniSignal['badge']))
                                    <span class="market-home-featured-mini-views">{{ $miniSignal['badge'] }}</span>
                                @endif
                                <span class="market-home-featured-mini-price">${{ number_format($featured['product']->price, 2) }}</span>
                                <a href="{{ route('products.show', $featured['product']->slug) }}" class="market-home-featured-cta-primary market-home-featured-mini-cta">View Product</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="market-home-empty">No featured products available yet.</p>
            @endif
        </div>
    </section>

    <div class="market-home-sold-ticker">
        <span class="market-home-sold-ticker-label">Recently sold</span>
        <div class="market-home-sold-ticker-track">
            @if(!empty($recentlySoldTicker))
                <div class="market-home-sold-ticker-marquee">
                    @foreach($recentlySoldTicker as $sold)
                        <span class="market-home-sold-ticker-item">
                            {{ $sold['product_name'] }} ×{{ $sold['quantity'] }} · {{ $sold['time'] }}
                        </span>
                    @endforeach
                    @foreach($recentlySoldTicker as $sold)
                        <span class="market-home-sold-ticker-item" aria-hidden="true">
                            {{ $sold['product_name'] }} ×{{ $sold['quantity'] }} · {{ $sold['time'] }}
                        </span>
                    @endforeach
                </div>
            @else
                <span class="market-home-sold-ticker-item">No recent sales yet.</span>
            @endif
        </div>
    </div>

    <section class="market-home-dual-grid">
        <article class="market-home-panel market-home-panel-trending">
            <div class="market-home-section-head">
                <h2>Trending Items</h2>
                <a href="{{ route('products.index') }}">Explore</a>
            </div>
            <div class="market-home-trending-scroll">
                @forelse($trendingItems as $item)
                    <article class="market-home-trending-card">
                        <a href="{{ route('products.show', $item->slug) }}" class="market-home-trending-image-link">
                            <img src="{{ $item->product_picture_url }}" alt="{{ $item->name }}" class="market-home-trending-image">
                        </a>
                        <div class="market-home-trending-body">
                            <a href="{{ route('products.show', $item->slug) }}" class="market-home-trending-title">{{ $item->name }}</a>
                            <div class="market-home-trending-meta">
                                <span>{{ $item->reviews_count }} reviews</span>
                                <span>${{ number_format($item->price, 2) }}</span>
                            </div>
                            <div class="market-home-trending-badges">
                                @if(!empty($liveSignals[(string) $item->id]['badge']))
                                    <span class="market-home-trending-badge">{{ $liveSignals[(string) $item->id]['badge'] }}</span>
                                @endif
                                <span class="market-home-trending-views">👁 {{ number_format($liveSignals[(string) $item->id]['views'] ?? 0) }}</span>
                            </div>
                            <div class="market-home-trending-activity">
                                <span class="market-home-activity-dot"></span>
                                <span>{{ $item->reviews_count >= 5 ? 'High activity' : 'Rising' }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <p class="market-home-empty-row">No trending items yet.</p>
                @endforelse
            </div>
        </article>

        <article class="market-home-panel">
            <div class="market-home-section-head">
                <h2>New Listings</h2>
                <a href="{{ route('products.index') }}">Latest</a>
            </div>
            <div class="market-home-new-grid">
                @forelse($newListings as $item)
                    <article class="market-home-new-card">
                        <a href="{{ route('products.show', $item->slug) }}" class="market-home-new-title">{{ $item->name }}</a>
                        <div class="market-home-new-meta">
                            <span>{{ $item->user->username }}</span>
                            <span class="market-home-new-time">{{ $item->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="market-home-new-views">👁 {{ number_format($liveSignals[(string) $item->id]['views'] ?? 0) }}</div>
                        <div class="market-home-new-price">${{ number_format($item->price, 2) }}</div>
                    </article>
                @empty
                    <p class="market-home-empty-row">No new listings yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="market-home-section">
        <div class="market-home-section-head">
            <h2>Recommended for You</h2>
            <a href="{{ route('wishlist.index') }}">Your wishlist</a>
        </div>
        <div class="market-home-product-grid">
            @forelse($recommendedForUser as $product)
                <article class="market-home-product-card">
                    <a href="{{ route('products.show', $product->slug) }}" class="market-home-product-image-link">
                        <img src="{{ $product->product_picture_url }}" alt="{{ $product->name }}" class="market-home-product-image">
                    </a>
                    <div class="market-home-product-body">
                        <a href="{{ route('products.show', $product->slug) }}" class="market-home-product-title">{{ $product->name }}</a>
                        <div class="market-home-product-meta">
                            <span>By {{ $product->user->username }}</span>
                            <span>{{ $product->category->name }}</span>
                        </div>
                        <div class="market-home-product-live">👁 {{ number_format($liveSignals[(string) $product->id]['views'] ?? 0) }} watching</div>
                        <div class="market-home-product-price">${{ number_format($product->price, 2) }}</div>
                    </div>
                </article>
            @empty
                <p class="market-home-empty">No recommendations yet.</p>
            @endforelse
        </div>
    </section>

    <section class="market-home-activity-summary">
        <h2>Activity Summary</h2>
        <div class="market-home-activity-metrics">
            <div><span>Active Orders</span><strong>{{ number_format($stats['active_orders']) }}</strong></div>
            <div><span>Wishlist Items</span><strong>{{ number_format($stats['wishlist_items']) }}</strong></div>
            <div><span>Messages</span><strong>{{ number_format($stats['messages']) }}</strong></div>
            <div><span>Vendor Rating</span><strong>{{ $stats['vendor_rating'] !== null ? $stats['vendor_rating'] . '%' : 'N/A' }}</strong></div>
        </div>
    </section>
</div>
@endsection
