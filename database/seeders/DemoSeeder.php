<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dispute;
use App\Models\DisputeMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Password!123';

    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $vendorRole = Role::firstOrCreate(['name' => 'vendor']);

        $admin = $this->demoUser('demo_admin');
        $vendor = $this->demoUser('demo_vendor');
        $buyer = $this->demoUser('demo_buyer');

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        $vendor->roles()->syncWithoutDetaching([$vendorRole->id]);

        VendorProfile::updateOrCreate(
            ['user_id' => $vendor->id],
            [
                'description' => 'Demo vendor profile for portfolio review.',
                'vendor_policy' => 'Ships demo orders promptly and supports local pickup windows.',
                'vacation_mode' => false,
            ]
        );

        $digital = Category::firstOrCreate(['name' => 'Digital']);
        $services = Category::firstOrCreate(['name' => 'Services']);
        $goods = Category::firstOrCreate(['name' => 'Goods']);

        $product = Product::updateOrCreate(
            ['user_id' => $vendor->id, 'name' => 'Privacy Starter Kit'],
            [
                'description' => 'Demo product showing stock, delivery options, and checkout flow.',
                'price' => 49.00,
                'type' => Product::TYPE_DIGITAL,
                'active' => true,
                'category_id' => $digital->id,
                'stock_amount' => 25,
                'measurement_unit' => Product::UNIT_PIECE,
                'delivery_options' => [
                    ['description' => 'Instant demo delivery', 'price' => 0],
                ],
                'bulk_options' => [
                    ['amount' => 3, 'price' => 129],
                ],
                'ships_from' => 'Worldwide',
                'ships_to' => 'Worldwide',
                'product_picture' => 'default-product-picture.png',
                'additional_photos' => [],
            ]
        );

        Product::updateOrCreate(
            ['user_id' => $vendor->id, 'name' => 'Local Pickup Consultation'],
            [
                'description' => 'Demo local pickup listing that preserves the legacy storage value.',
                'price' => 125.00,
                'type' => Product::TYPE_LOCAL_PICKUP,
                'active' => true,
                'category_id' => $services->id,
                'stock_amount' => 4,
                'measurement_unit' => Product::UNIT_HOUR,
                'delivery_options' => [
                    ['description' => 'Weekday pickup window', 'price' => 0],
                ],
                'bulk_options' => [],
                'ships_from' => 'Worldwide',
                'ships_to' => 'Worldwide',
                'product_picture' => 'default-product-picture.png',
                'additional_photos' => [],
            ]
        );

        Product::updateOrCreate(
            ['user_id' => $vendor->id, 'name' => 'Vendor Operations Notebook'],
            [
                'description' => 'Demo cargo listing for admin and vendor screens.',
                'price' => 19.00,
                'type' => Product::TYPE_CARGO,
                'active' => true,
                'category_id' => $goods->id,
                'stock_amount' => 12,
                'measurement_unit' => Product::UNIT_PIECE,
                'delivery_options' => [
                    ['description' => 'Standard tracked shipping', 'price' => 6.50],
                ],
                'bulk_options' => [],
                'ships_from' => 'Worldwide',
                'ships_to' => 'Worldwide',
                'product_picture' => 'default-product-picture.png',
                'additional_photos' => [],
            ]
        );

        $order = Order::updateOrCreate(
            ['unique_url' => 'demo-order-mercatoria-001'],
            [
                'user_id' => $buyer->id,
                'vendor_id' => $vendor->id,
                'subtotal' => 49.00,
                'commission' => 2.45,
                'total' => 51.45,
                'status' => Order::STATUS_DISPUTED,
                'shipping_address' => 'Demo-only shipping address',
                'delivery_option' => 'Instant demo delivery',
                'encrypted_message' => 'Demo encrypted message placeholder',
                'is_paid' => true,
                'is_sent' => true,
                'is_completed' => false,
                'is_disputed' => true,
                'payment_address' => 'demo_payment_address_not_real',
                'payment_address_index' => 0,
                'required_xmr_amount' => 0.100000000000,
                'total_received_xmr' => 0.100000000000,
                'xmr_usd_rate' => 514.50,
                'paid_at' => now()->subDays(2),
                'sent_at' => now()->subDay(),
                'disputed_at' => now(),
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order->id, 'product_id' => $product->id],
            [
                'product_name' => $product->name,
                'product_description' => $product->description,
                'price' => $product->price,
                'quantity' => 1,
                'measurement_unit' => $product->measurement_unit,
                'delivery_option' => ['description' => 'Instant demo delivery', 'price' => 0],
                'bulk_option' => null,
                'delivery_text' => 'Demo fulfillment note.',
            ]
        );

        $dispute = Dispute::updateOrCreate(
            ['order_id' => $order->id],
            [
                'status' => Dispute::STATUS_ACTIVE,
                'reason' => 'Demo dispute for admin workflow review.',
            ]
        );

        DisputeMessage::firstOrCreate(
            ['dispute_id' => $dispute->id, 'user_id' => $buyer->id],
            ['message' => 'Demo buyer dispute message.']
        );

        DisputeMessage::firstOrCreate(
            ['dispute_id' => $dispute->id, 'user_id' => $vendor->id],
            ['message' => 'Demo vendor response message.']
        );
    }

    private function demoUser(string $username): User
    {
        return User::updateOrCreate(
            ['username' => $username],
            [
                'password' => Hash::make(self::PASSWORD),
                'mnemonic' => 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about',
                'reference_id' => strtoupper($username) . '001',
            ]
        );
    }
}
