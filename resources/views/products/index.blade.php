@extends('layouts.app')

@section('content')

<div class="products-index-container">
    <div class="products-index-content">
        <div class="products-index-filter-card">
            <form action="{{ route('products.index') }}" method="GET">
                <div class="products-index-toolbar">
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Search products"
                           minlength="1"
                           maxlength="80"
                           class="products-index-input products-index-input-search">

                    <select name="vendor" id="vendor" class="products-index-select" aria-label="Filter by vendor">
                        <option value="">Vendors</option>
                        @foreach($vendorOptions as $vendor)
                            <option value="{{ $vendor }}" {{ ($filters['vendor'] ?? '') === $vendor ? 'selected' : '' }}>
                                {{ $vendor }}
                            </option>
                        @endforeach
                    </select>

                    <select name="type" id="type" class="products-index-select" aria-label="Filter by type">
                        <option value="">Type</option>
                        <option value="digital" {{ ($currentType === 'digital') ? 'selected' : '' }}>Digital</option>
                        <option value="cargo" {{ ($currentType === 'cargo') ? 'selected' : '' }}>Cargo</option>
                        <option value="deaddrop" {{ ($currentType === 'deaddrop') ? 'selected' : '' }}>Local Pickup</option>
                    </select>

                    <select name="category" id="category" class="products-index-select" aria-label="Filter by category">
                        <option value="">Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category'] ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="sort_price" id="sort_price" class="products-index-select" aria-label="Sort by price">
                        <option value="">Newest</option>
                        <option value="asc" {{ ($filters['sort_price'] ?? '') === 'asc' ? 'selected' : '' }}>Price: Low to High</option>
                        <option value="desc" {{ ($filters['sort_price'] ?? '') === 'desc' ? 'selected' : '' }}>Price: High to Low</option>
                    </select>

                    <div class="products-index-toolbar-actions">
                        <a href="{{ route('products.index') }}" class="products-index-button products-index-button-secondary">Reset</a>
                        <button type="submit" class="products-index-button products-index-button-primary">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($products->isEmpty())
        <div class="products-index-empty">
            <p>No products found.</p>
        </div>
    @else
        <x-products 
            :products="$products"
        />
    @endif
</div>

@endsection
