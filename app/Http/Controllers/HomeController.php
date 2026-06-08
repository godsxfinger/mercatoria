<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\XmrPriceController;
use App\Models\Category;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReviews;
use App\Models\Wishlist;
use App\Models\Advertisement;

class HomeController extends Controller
{
    /**
     * Show the home page.
     *
     * @return \Illuminate\View\View
     */
    public function index(XmrPriceController $xmrPriceController)
    {
        $user = Auth::user();

        // Active popup used as announcement source when available
        $popup = \App\Models\Popup::getActive();

        // Current XMR price for conversion
        $xmrPrice = $xmrPriceController->getXmrPrice();
        $measurementUnits = \App\Models\Product::getMeasurementUnits();

        $formatMarketplaceProduct = function (Product $product, bool $isSponsored = false, ?int $adSlot = null) use ($xmrPrice, $measurementUnits) {
            $formattedMeasurementUnit = $measurementUnits[$product->measurement_unit] ?? $product->measurement_unit;
            $productXmrPrice = (is_numeric($xmrPrice) && $xmrPrice > 0) ? $product->price / $xmrPrice : null;

            return [
                'product' => $product,
                'vendor' => $product->user,
                'measurement_unit' => $formattedMeasurementUnit,
                'xmr_price' => $productXmrPrice,
                'bulk_options' => $product->getFormattedBulkOptions($xmrPrice),
                'delivery_options' => $product->getFormattedDeliveryOptions($xmrPrice),
                'is_sponsored' => $isSponsored,
                'ad_slot' => $adSlot,
            ];
        };

        // Sponsored products from active paid advertisements (ordered by slot number)
        $activeAdvertisements = Advertisement::getActiveAdvertisements();
        $sponsoredFeaturedProducts = [];
        foreach ($activeAdvertisements as $ad) {
            if (!$ad->product || $ad->product->trashed() || !$ad->product->active) {
                continue;
            }
            $sponsoredFeaturedProducts[] = $formatMarketplaceProduct(
                $ad->product,
                true,
                (int) $ad->slot_number
            );
        }

        // Featured products (non-sponsored fill)
        $featuredProducts = \App\Models\FeaturedProduct::getAllFeaturedProducts();
        $formattedFeaturedProducts = [];
        $seenFeaturedProductIds = collect($sponsoredFeaturedProducts)
            ->pluck('product.id')
            ->filter()
            ->values()
            ->all();

        foreach ($featuredProducts as $featured) {
            if (!$featured->product || $featured->product->trashed()) {
                continue;
            }
            if (in_array($featured->product->id, $seenFeaturedProductIds, true)) {
                continue;
            }

            $formattedFeaturedProducts[] = $formatMarketplaceProduct($featured->product, false, null);
        }

        // Merge sponsored + featured and cap to homepage display limit.
        $formattedFeaturedProducts = collect($sponsoredFeaturedProducts)
            ->merge($formattedFeaturedProducts)
            ->take(5)
            ->values()
            ->all();

        // Fallback: if still empty, populate with highest-rated active products
        if (empty($formattedFeaturedProducts)) {
            $fallbackProducts = Product::active()
                ->with(['user:id,username', 'category:id,name'])
                ->withCount([
                    'reviews as total_reviews_count',
                    'reviews as positive_reviews_count' => function ($query) {
                        $query->where('sentiment', ProductReviews::SENTIMENT_POSITIVE);
                    },
                ])
                ->latest()
                ->take(36)
                ->get()
                ->sortByDesc(function ($product) {
                    $total = (int) $product->total_reviews_count;
                    if ($total === 0) {
                        return 0;
                    }
                    return $product->positive_reviews_count / $total;
                })
                ->take(5)
                ->values();

            $formattedFeaturedProducts = $fallbackProducts
                ->map(function ($product) use ($formatMarketplaceProduct) {
                    return $formatMarketplaceProduct($product, false, null);
                })
                ->all();
        }

        $trendingItems = Product::active()
            ->with(['user:id,username', 'category:id,name'])
            ->withCount('reviews')
            ->orderByDesc('reviews_count')
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $newListings = Product::active()
            ->with(['user:id,username', 'category:id,name'])
            ->latest()
            ->take(4)
            ->get();

        $categoryOverview = Category::query()
            ->whereNull('parent_id')
            ->withCount(['products as active_products_count' => function ($query) {
                $query->where('active', true)->whereNull('deleted_at');
            }])
            ->orderByDesc('active_products_count')
            ->take(8)
            ->get();

        $recommendedForUser = collect();
        $preferredCategoryIds = Wishlist::where('wishlists.user_id', $user->id)
            ->join('products', 'wishlists.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->pluck('products.category_id')
            ->filter()
            ->unique()
            ->values();

        if ($preferredCategoryIds->isNotEmpty()) {
            $recommendedForUser = Product::active()
                ->with(['user:id,username', 'category:id,name'])
                ->whereIn('category_id', $preferredCategoryIds)
                ->latest()
                ->take(8)
                ->get();
        }

        if ($recommendedForUser->isEmpty()) {
            $recommendedForUser = Product::active()
                ->with(['user:id,username', 'category:id,name'])
                ->inRandomOrder()
                ->take(8)
                ->get();
        }

        $activeOrdersCount = Order::where('user_id', $user->id)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED])
            ->count();

        $wishlistCount = Wishlist::where('user_id', $user->id)->count();
        $messageCount = $user->conversations()->count();

        $vendorRating = null;
        if ($user->isVendor()) {
            $productIds = Product::where('user_id', $user->id)->pluck('id');
            if ($productIds->isNotEmpty()) {
                $totalReviews = ProductReviews::whereIn('product_id', $productIds)->count();
                if ($totalReviews > 0) {
                    $positiveReviews = ProductReviews::whereIn('product_id', $productIds)
                        ->where('sentiment', ProductReviews::SENTIMENT_POSITIVE)
                    ->count();
                    $vendorRating = round(($positiveReviews / $totalReviews) * 100);
                }
            }
        }

        $announcementText = $popup
            ? trim($popup->title . ' — ' . strip_tags($popup->message))
            : 'Marketplace live: browse trending listings, check newly added products, and track your active activity.';

        $recentOrders = Order::where('user_id', $user->id)
            ->orWhere('vendor_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'unique_url', 'status', 'created_at', 'total']);

        $recentConversations = Message::conversation()
            ->where(function ($query) use ($user) {
                $query->where('user_id_1', $user->id)
                    ->orWhere('user_id_2', $user->id);
            })
            ->with(['user1:id,username', 'user2:id,username'])
            ->orderBy('last_message_at', 'desc')
            ->limit(3)
            ->get(['id', 'user_id_1', 'user_id_2', 'last_message_at']);

        $allProductIds = collect($formattedFeaturedProducts)->pluck('product.id')
            ->merge($trendingItems->pluck('id'))
            ->merge($newListings->pluck('id'))
            ->merge($recommendedForUser->pluck('id'))
            ->unique()
            ->values();

        $soldCountsByProduct = collect();
        if ($allProductIds->isNotEmpty()) {
            $soldCountsByProduct = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('order_items.product_id', $allProductIds)
                ->where('orders.is_paid', true)
                ->groupBy('order_items.product_id')
                ->selectRaw('order_items.product_id as product_id, COALESCE(SUM(order_items.quantity), 0) as sold_qty')
                ->pluck('sold_qty', 'product_id');
        }

        $liveSignals = [];
        foreach ($allProductIds as $productId) {
            $sold = (int) ($soldCountsByProduct[$productId] ?? 0);
            $isTrending = $trendingItems->contains('id', $productId);

            $views = max(12, (int) (($sold * 11) + ($isTrending ? 90 : 38) + random_int(0, 45)));
            $badge = null;
            if ($isTrending && $sold >= 10) {
                $badge = 'Hot';
            } elseif ($isTrending) {
                $badge = 'Trending';
            } elseif ($sold >= 5) {
                $badge = 'Rising';
            }

            $liveSignals[(string) $productId] = [
                'views' => $views,
                'sold' => $sold,
                'badge' => $badge,
            ];
        }

        $recentlySoldTicker = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.is_paid', true)
            ->whereNotNull('order_items.product_name')
            ->latest('orders.paid_at')
            ->take(10)
            ->get([
                'order_items.product_name as product_name',
                'order_items.quantity as quantity',
                'orders.paid_at as paid_at',
            ])
            ->map(function ($row) {
                return [
                    'product_name' => $row->product_name,
                    'quantity' => (int) $row->quantity,
                    'time' => \Carbon\Carbon::parse($row->paid_at)->diffForHumans(),
                ];
            });

        return view('home', [
            'username' => $user->username,
            'announcementText' => $announcementText,
            'featuredProducts' => $formattedFeaturedProducts,
            'trendingItems' => $trendingItems,
            'newListings' => $newListings,
            'categoryOverview' => $categoryOverview,
            'recommendedForUser' => $recommendedForUser,
            'stats' => [
                'active_orders' => $activeOrdersCount,
                'wishlist_items' => $wishlistCount,
                'messages' => $messageCount,
                'vendor_rating' => $vendorRating,
            ],
            'recentOrders' => $recentOrders,
            'recentConversations' => $recentConversations,
            'liveSignals' => $liveSignals,
            'recentlySoldTicker' => $recentlySoldTicker,
        ]);
    }
}
