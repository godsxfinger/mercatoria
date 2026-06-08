@extends('layouts.app')

@section('content')

<div class="wishlist-index-container">
    <div class="wishlist-index-header">
        <h1 class="wishlist-index-title">{{ $title }}</h1>
        @if(!$products->isEmpty())
            <form action="{{ route('wishlist.clear') }}" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit" class="wishlist-index-clear-btn">
                    Clear Wishlist
                </button>
            </form>
        @endif
    </div>

    @if($products->isEmpty())
        <div class="wishlist-index-empty">
            <div class="wishlist-index-empty-icon" aria-hidden="true">♡</div>
            <h2 class="wishlist-index-empty-title">You haven’t saved anything yet.</h2>
            <p class="wishlist-index-empty-text">Start exploring and save products you love.</p>
            <div class="wishlist-index-empty-actions">
                <a href="{{ route('products.index') }}" class="wishlist-index-browse-btn">
                    Browse Products
                </a>
                <a href="{{ route('home') }}" class="wishlist-index-trending-link">
                    View Trending
                </a>
            </div>
        </div>
    @else
        <x-products 
            :products="$products"
        />
    @endif
</div>
@endsection
