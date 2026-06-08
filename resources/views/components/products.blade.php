@props([
    'products'  // Required: collection of products
])
<div class="product-card-grid">
    @foreach($products as $product)
        <a href="{{ route('products.show', $product->slug) }}" class="product-card">
            @auth
            <form action="{{ Auth::user()->hasWishlisted($product->id) 
                ? route('wishlist.destroy', $product) 
                : route('wishlist.store', $product) }}" 
                method="POST">
                @csrf
                @if(Auth::user()->hasWishlisted($product->id))
                    @method('DELETE')
                @endif
                <button type="submit" class="product-card-wishlist-button {{ Auth::user()->hasWishlisted($product->id) ? 'active' : '' }}" title="{{ Auth::user()->hasWishlisted($product->id) ? 'Remove from Wishlist' : 'Add to Wishlist' }}">
                    <img src="{{ asset('icons/wishlist.png') }}" alt="Wishlist" class="product-card-wishlist-icon">
                </button>
            </form>
            @endauth
            <div class="product-card-image">
                <img src="{{ $product->product_picture_url }}" alt="{{ $product->name }}">
                <span class="product-card-quick-view">Quick view</span>
            </div>
            <div class="product-card-content">
                <div class="product-card-rating-inline">
                    @if($product->getPositiveReviewPercentage() !== null)
                        ★ {{ number_format($product->getPositiveReviewPercentage(), 0) }}% positive
                    @else
                        ☆ No reviews yet
                    @endif
                </div>

                <div class="product-card-main-row">
                    <h3 class="product-card-name">{{ $product->name }}</h3>
                    <span class="product-card-price">${{ number_format($product->price, 2) }}</span>
                </div>

                <div class="product-card-vendor">
                    By {{ $product->user->username }}
                </div>
            </div>
        </a>
    @endforeach
</div>
<div class="product-card-pagination">
    {{ $products->links() }}
</div>
