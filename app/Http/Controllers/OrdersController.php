<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Dispute;
use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\RefundBuyer;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\XmrPriceController;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Log;

class OrdersController extends Controller
{
    public function __construct(
        private PaymentGateway $payments,
        private CreateOrder $createOrder,
        private CompleteOrder $completeOrder,
        private RefundBuyer $refundBuyer,
    ) {
    }
    /**
     * Display a listing of the user's orders.
     */
    public function index(Request $request)
    {
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'newest');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');
        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [10, 20, 50];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $ordersQuery = Order::where('user_id', Auth::id())
            ->with(['items', 'vendor']);

        if ($status === 'completed') {
            $ordersQuery->where('status', Order::STATUS_COMPLETED);
        } elseif ($status === 'cancelled') {
            $ordersQuery->where('status', Order::STATUS_CANCELLED);
        } elseif ($status === 'waiting') {
            $ordersQuery->whereIn('status', [
                Order::STATUS_WAITING_PAYMENT,
                Order::STATUS_PAYMENT_RECEIVED,
                Order::STATUS_PRODUCT_SENT,
                Order::STATUS_DISPUTED,
            ]);
        } else {
            $status = 'all';
        }

        if ($from !== '') {
            $ordersQuery->whereDate('created_at', '>=', $from);
        }

        if ($to !== '') {
            $ordersQuery->whereDate('created_at', '<=', $to);
        }

        switch ($sort) {
            case 'oldest':
                $ordersQuery->orderBy('created_at', 'asc');
                break;
            case 'highest_amount':
                $ordersQuery->orderBy('total', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'lowest_amount':
                $ordersQuery->orderBy('total', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'newest':
            default:
                $sort = 'newest';
                $ordersQuery->orderBy('created_at', 'desc');
                break;
        }

        $orders = $ordersQuery->paginate($perPage)->withQueryString();
        
        return view('orders.index', [
            'orders' => $orders,
            'statusFilter' => $status,
            'sortFilter' => $sort,
            'dateFrom' => $from,
            'dateTo' => $to,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Display the specified order.
     */
    public function show($uniqueUrl)
    {
        // Process any orders that need auto status changes
        Order::processAllAutoStatusChanges();
        
        $order = Order::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Check if the user is either the buyer or the vendor
        if ($order->user_id !== Auth::id() && $order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized access.');
        }

        // Determine if the current user is the buyer or vendor
        $isBuyer = $order->user_id === Auth::id();

        // For buyers with unpaid orders, check if a payment address exists
        // and handle payment processing
        $qrCode = null;
        if ($isBuyer && $order->status === Order::STATUS_WAITING_PAYMENT) {
            // First check if the order has an expired payment
            if ($order->isExpired() && !empty($order->payment_address)) {
                // Handle expired payment (cancels the order)
                $order->handleExpiredPayment();
                
                // Refresh the order after cancellation
                $order->refresh();
                
                if ($order->status === Order::STATUS_CANCELLED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically cancelled because the payment window has expired.');
                }
            }
            
            // Check if the order should be auto-cancelled (not sent within 96 hours)
            if ($order->shouldAutoCancelIfNotSent()) {
                $order->autoCancelIfNotSent();
                $order->refresh();
                
                if ($order->status === Order::STATUS_CANCELLED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically cancelled because the vendor did not mark it as sent within 96 hours (4 days) after payment.');
                }
            }
            
            // Check if the order should be auto-completed (not marked completed within 192 hours after being sent)
            if ($order->shouldAutoCompleteIfNotConfirmed()) {
                $order->autoCompleteIfNotConfirmed();
                $order->refresh();
                
                if ($order->status === Order::STATUS_COMPLETED) {
                    return redirect()->route('orders.show', $order->unique_url)
                        ->with('info', 'This order has been automatically marked as completed because it was not confirmed within 192 hours (8 days) after being marked as sent.');
                }
            }
            
            // Only generate a payment address if none exists and the order isn't cancelled
            if (empty($order->payment_address) && $order->status === Order::STATUS_WAITING_PAYMENT) {
                try {
                    // Get current XMR/USD rate
                    $xmrPriceController = new XmrPriceController();
                    $xmrRate = $xmrPriceController->getXmrPrice();
                    
                    if ($xmrRate === 'UNAVAILABLE') {
                        return redirect()->back()->with('error', 'Unable to get XMR price. Please try again later.');
                    }
                    
                    // Calculate required XMR amount
                    $requiredXmrAmount = $order->calculateRequiredXmrAmount($xmrRate);
                    
                    // Update order with XMR details
                    $order->required_xmr_amount = $requiredXmrAmount;
                    $order->xmr_usd_rate = $xmrRate;
                    $order->save();
                    
                    $this->payments->createPaymentAddress($order);
                } catch (\Exception $e) {
                    Log::error('Error setting up payment: ' . $e->getMessage());
                    return redirect()->back()->with('error', 'Error setting up payment: ' . $e->getMessage());
                }
            }
            
            // Check for new payments
            try {
                $status = $this->payments->checkPaymentStatus($order);
                $order->total_received_xmr = $status->receivedAmount;

                if ($status->paid && !$order->is_paid) {
                    $order->status = Order::STATUS_PAYMENT_RECEIVED;
                    $order->is_paid = true;
                    $order->paid_at = $status->paidAt ?? now();
                    $order->payment_completed_at = $status->paidAt ?? now();
                }

                $order->save();
            } catch (\Exception $e) {
                Log::error('Error checking payments: ' . $e->getMessage());
            }
            
            // Refresh order data after potential updates
            $order->refresh();
            
            // Generate QR code if payment is not completed
            if (!$order->is_paid && $order->payment_address) {
                try {
                    $qrCode = $this->generateQrCode($order->payment_address);
                } catch (\Exception $e) {
                    Log::error('Error generating QR code: ' . $e->getMessage());
                }
            }
        }

        // If the user is the buyer and the order is completed, prepare existing reviews for each order item.
        if ($isBuyer && $order->status === 'completed') {
            foreach ($order->items as $item) {
                $item->existingReview = \App\Models\ProductReviews::where('user_id', Auth::id())
                    ->where('order_item_id', $item->id)
                    ->first();
            }
        }
        
        // Get dispute if it exists
        $dispute = $order->dispute;
        
        // Calculate total number of items, accounting for bulk options
        $totalItems = 0;
        foreach($order->items as $item) {
            if($item->bulk_option && isset($item->bulk_option['amount'])) {
                $totalItems += $item->quantity * $item->bulk_option['amount'];
            } else {
                $totalItems += $item->quantity;
            }
        }
        
        return view('orders.show', [
            'order' => $order,
            'isBuyer' => $isBuyer,
            'dispute' => $dispute,
            'qrCode' => $qrCode,
            'totalItems' => $totalItems
        ]);
    }

    /**
     * Create a new order from the cart items.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $cartItems = Cart::where('user_id', $user->id)->with(['product', 'product.user'])->get();
            
            if ($cartItems->isEmpty()) {
                return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
            }
            
            $order = $this->createOrder->handle($user, $cartItems);
            
            return redirect()->route('orders.show', $order->unique_url)
                ->with('success', 'Order created successfully. Please complete the payment.');
                
        } catch (\Exception $e) {
            Log::error('Failed to create order: ' . $e->getMessage());
            return redirect()->route('cart.checkout')
                ->with('error', 'Failed to create order. Please try again.');
        }
    }

    /**
     * Generate a QR code for the given address.
     */
    private function generateQrCode($address)
    {
        try {
            $result = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data($address)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                ->size(300)
                ->margin(10)
                ->build();
            
            return $result->getDataUri();
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Mark the order as sent.
     */
    public function markAsSent($uniqueUrl)
    {
        $order = Order::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the vendor can mark as sent
        if ($order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->markAsSent()) {
            return redirect()->route('vendor.sales.show', $order->unique_url)
                ->with('success', 'Product marked as sent. The buyer has been notified.');
        }

        return redirect()->route('vendor.sales.show', $order->unique_url)
            ->with('error', 'Unable to mark as sent at this time.');
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted($uniqueUrl)
    {
        $order = Order::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the buyer can mark as completed
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($this->completeOrder->handle($order)) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('success', 'Order marked as completed and payment has been sent to the vendor. Thank you for your purchase.');
        }

        return redirect()->route('orders.show', $order->unique_url)
            ->with('error', 'Unable to mark as completed at this time.');
    }

    /**
     * Mark the order as cancelled.
     */
    public function markAsCancelled($uniqueUrl)
    {
        $order = Order::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - both buyer and vendor can cancel
        if ($order->user_id !== Auth::id() && $order->vendor_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Check if order is in a status that can be cancelled
        if ($order->status === Order::STATUS_COMPLETED) {
            return redirect()->back()->with('error', 'Completed orders cannot be cancelled.');
        }

        if (in_array($order->status, [Order::STATUS_PAYMENT_RECEIVED, Order::STATUS_PRODUCT_SENT, Order::STATUS_DISPUTED], true)) {
            $cancelled = $this->refundBuyer->handle($order);
        } else {
            $cancelled = $order->markAsCancelled();
        }

        if ($cancelled) {
            // Determine the redirect route based on whether the user is buyer or vendor
            $isBuyer = $order->user_id === Auth::id();
            $route = $isBuyer ? 'orders.show' : 'vendor.sales.show';
            
            return redirect()->route($route, $order->unique_url)
                ->with('success', 'Order has been closed successfully.');
        }

        return redirect()->back()->with('error', 'Unable to cancel the order at this time.');
    }

    /**
     * Submit a review for a product in a completed order.
     */
    public function submitReview(Request $request, $uniqueUrl, $orderItemId)
    {
        // Find the order
        $order = Order::findByUrl($uniqueUrl);
        
        if (!$order) {
            abort(404);
        }

        // Verify ownership - only the buyer can submit reviews
        if ($order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        // Verify order is completed
        if ($order->status !== Order::STATUS_COMPLETED) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('error', 'You can only review products from completed orders.');
        }

        // Find the order item
        $orderItem = $order->items()->where('id', $orderItemId)->first();
        
        if (!$orderItem) {
            abort(404);
        }

        // Check if a review already exists for this item
        $existingReview = \App\Models\ProductReviews::where('user_id', Auth::id())
            ->where('order_item_id', $orderItem->id)
            ->first();
            
        if ($existingReview) {
            return redirect()->route('orders.show', $order->unique_url)
                ->with('error', 'You have already reviewed this product.');
        }

        // Validate the request
        $validated = $request->validate([
            'review_text' => 'required|string|min:8|max:800',
            'sentiment' => 'required|in:positive,mixed,negative',
        ]);

        // Create the review
        \App\Models\ProductReviews::create([
            'product_id' => $orderItem->product_id,
            'user_id' => Auth::id(),
            'order_id' => $order->id,
            'order_item_id' => $orderItem->id,
            'review_text' => $validated['review_text'],
            'sentiment' => $validated['sentiment'],
        ]);

        return redirect()->route('orders.show', $order->unique_url)
            ->with('success', 'Your review has been submitted successfully.');
    }
}
