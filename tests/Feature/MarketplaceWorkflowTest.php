<?php

namespace Tests\Feature;

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\RefundBuyer;
use App\Models\BannedUser;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Orders\InvalidOrderTransitionException;
use App\Services\Orders\OrderStateService;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentRequest;
use App\Services\Payments\PaymentStatus;
use App\Services\Payments\RefundResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MarketplaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_login_workflow(): void
    {
        $this->withSession(['captcha_code' => 'ABCD'])
            ->post('/register', [
                'username' => 'buyerx',
                'password' => 'Password!123',
                'password_confirmation' => 'Password!123',
                'captcha' => 'ABCD',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['username' => 'buyerx']);

        $this->withSession(['captcha_code' => 'WXYZ'])
            ->post('/login', [
                'username' => 'buyerx',
                'password' => 'Password!123',
                'captcha' => 'WXYZ',
            ])
            ->assertRedirect('home');

        $this->assertAuthenticated();
    }

    public function test_banned_user_cannot_login(): void
    {
        $user = $this->user('blockeduser');
        BannedUser::create([
            'user_id' => $user->id,
            'reason' => 'Test ban',
            'banned_until' => now()->addDay(),
        ]);

        $this->withSession(['captcha_code' => 'LOCK'])
            ->post('/login', [
                'username' => 'blockeduser',
                'password' => 'Password!123',
                'captcha' => 'LOCK',
            ])
            ->assertRedirect(route('banned'));

        $this->assertGuest();
    }

    public function test_admin_and_vendor_middleware_block_non_privileged_users(): void
    {
        $user = $this->user('regular_user');

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->get('/vendor')->assertForbidden();
    }

    public function test_vendor_can_create_local_pickup_product(): void
    {
        $vendor = $this->vendor();
        $category = Category::create(['name' => 'Services']);

        $this->actingAs($vendor)
            ->post('/vendor/products/local-pickup', [
                'name' => 'Pickup Demo Product',
                'description' => 'A portfolio-safe local pickup product.',
                'price' => 25,
                'category_id' => $category->id,
                'stock_amount' => 5,
                'measurement_unit' => Product::UNIT_PIECE,
                'ships_from' => 'Worldwide',
                'ships_to' => 'Worldwide',
                'delivery_options' => [
                    ['description' => 'Weekday pickup window', 'price' => 0],
                ],
            ])
            ->assertRedirect(route('vendor.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Pickup Demo Product',
            'type' => Product::TYPE_LOCAL_PICKUP,
            'user_id' => $vendor->id,
        ]);
    }

    public function test_cart_enforces_single_vendor_and_stock_limits(): void
    {
        $buyer = $this->user('cart_buyer');
        $firstProduct = $this->product($this->vendor('vendor_one'), 'Vendor One Product');
        $secondProduct = $this->product($this->vendor('vendor_two'), 'Vendor Two Product');

        Cart::create([
            'user_id' => $buyer->id,
            'product_id' => $firstProduct->id,
            'quantity' => 1,
            'price' => $firstProduct->price,
            'selected_delivery_option' => ['description' => 'Standard delivery', 'price' => 0],
        ]);

        $vendorCheck = Cart::validateProductAddition($buyer, $secondProduct);
        $stockCheck = Cart::validateStockAvailability($firstProduct->forceFill(['stock_amount' => 1]), 2);

        $this->assertFalse($vendorCheck['valid']);
        $this->assertSame('different_vendor', $vendorCheck['reason']);
        $this->assertFalse($stockCheck['valid']);
        $this->assertSame('insufficient_stock', $stockCheck['reason']);
    }

    public function test_order_creation_from_cart(): void
    {
        $buyer = $this->user('order_buyer');
        $product = $this->product($this->vendor(), 'Orderable Product');

        Cart::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
            'selected_delivery_option' => ['description' => 'Standard delivery', 'price' => 3],
        ]);

        $order = app(CreateOrder::class)->handle($buyer, Cart::with('product')->where('user_id', $buyer->id)->get());

        $this->assertSame(Order::STATUS_WAITING_PAYMENT, $order->status);
        $this->assertCount(1, $order->items);
        $this->assertDatabaseMissing('cart', ['user_id' => $buyer->id]);
    }

    public function test_order_status_transitions_are_validated(): void
    {
        $order = $this->order(Order::STATUS_WAITING_PAYMENT);
        $states = app(OrderStateService::class);

        $states->transition($order, Order::STATUS_PAYMENT_RECEIVED);
        $this->assertTrue($order->fresh()->is_paid);

        $this->expectException(InvalidOrderTransitionException::class);
        $states->transition($order->fresh(), Order::STATUS_CANCELLED);
    }

    public function test_payment_received_and_refund_flows_use_payment_gateway(): void
    {
        $this->app->instance(PaymentGateway::class, new class implements PaymentGateway {
            public function createPaymentAddress(Order $order): PaymentRequest
            {
                return new PaymentRequest('test-address', 7, now()->addHour());
            }

            public function checkPaymentStatus(Order $order): PaymentStatus
            {
                return new PaymentStatus(true, 0.5, now());
            }

            public function refund(Order $order): RefundResult
            {
                return new RefundResult(true, 0.5, 'refund-address');
            }
        });

        $order = $this->order(Order::STATUS_WAITING_PAYMENT);
        $status = app(PaymentGateway::class)->checkPaymentStatus($order);

        if ($status->paid) {
            app(OrderStateService::class)->transition($order, Order::STATUS_PAYMENT_RECEIVED);
        }

        $this->assertTrue($order->fresh()->is_paid);
        $this->assertTrue(app(RefundBuyer::class)->handle($order->fresh()));
        $this->assertSame(Order::STATUS_REFUNDED, $order->fresh()->status);
    }

    public function test_dispute_creation_and_message_authorization(): void
    {
        $buyer = $this->user('message_buyer');
        $vendor = $this->vendor('message_vendor');
        $outsider = $this->user('outsider_user');
        $order = $this->order(Order::STATUS_PRODUCT_SENT, $buyer, $vendor);

        $dispute = $order->openDispute('Delivery issue for test coverage.');
        $conversation = Message::createConversation($buyer->id, $vendor->id);

        $this->assertInstanceOf(Dispute::class, $dispute);
        $this->assertTrue(Gate::forUser($buyer)->allows('view', $conversation));
        $this->assertTrue(Gate::forUser($vendor)->allows('view', $conversation));
        $this->assertFalse(Gate::forUser($outsider)->allows('view', $conversation));
    }

    private function user(string $username = 'buyer'): User
    {
        return User::create([
            'username' => $username,
            'password' => Hash::make('Password!123'),
        ]);
    }

    private function vendor(string $username = 'vendor'): User
    {
        $vendor = $this->user($username);
        $role = Role::firstOrCreate(['name' => 'vendor']);
        $vendor->roles()->attach($role);

        return $vendor;
    }

    private function product(User $vendor, string $name): Product
    {
        $category = Category::firstOrCreate(['name' => 'Digital']);

        return Product::createDigital([
            'user_id' => $vendor->id,
            'name' => $name,
            'description' => 'Test product description.',
            'price' => 10,
            'category_id' => $category->id,
            'stock_amount' => 10,
            'measurement_unit' => Product::UNIT_PIECE,
            'delivery_options' => [
                ['description' => 'Standard delivery', 'price' => 0],
            ],
            'bulk_options' => [],
            'ships_from' => 'Worldwide',
            'ships_to' => 'Worldwide',
        ]);
    }

    private function order(string $status, ?User $buyer = null, ?User $vendor = null): Order
    {
        $buyer ??= $this->user('order_user_' . str()->random(6));
        $vendor ??= $this->vendor('order_vendor_' . str()->random(6));

        return Order::create([
            'user_id' => $buyer->id,
            'vendor_id' => $vendor->id,
            'subtotal' => 10,
            'commission' => 0.5,
            'total' => 10.5,
            'status' => $status,
            'is_paid' => in_array($status, [
                Order::STATUS_PAYMENT_RECEIVED,
                Order::STATUS_PRODUCT_SENT,
                Order::STATUS_COMPLETED,
                Order::STATUS_DISPUTED,
            ], true),
            'is_sent' => in_array($status, [
                Order::STATUS_PRODUCT_SENT,
                Order::STATUS_COMPLETED,
                Order::STATUS_DISPUTED,
            ], true),
            'is_disputed' => $status === Order::STATUS_DISPUTED,
        ]);
    }
}
