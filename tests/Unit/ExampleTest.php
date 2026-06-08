<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_product_delivery_options_require_valid_descriptions_and_prices(): void
    {
        $product = new Product();

        $this->assertTrue($product->validateDeliveryOptions([
            ['description' => 'Standard shipping', 'price' => 5.50],
        ]));

        $this->assertFalse($product->validateDeliveryOptions([]));
        $this->assertFalse($product->validateDeliveryOptions([
            ['description' => '', 'price' => 5.50],
        ]));
        $this->assertFalse($product->validateDeliveryOptions([
            ['description' => 'Standard shipping', 'price' => -1],
        ]));
    }

    public function test_product_bulk_options_are_optional_but_limited_and_positive(): void
    {
        $product = new Product();

        $this->assertTrue($product->validateBulkOptions(null));
        $this->assertTrue($product->validateBulkOptions([
            ['amount' => 10, 'price' => 80],
        ]));
        $this->assertFalse($product->validateBulkOptions([
            ['amount' => 0, 'price' => 80],
        ]));
        $this->assertFalse($product->validateBulkOptions([
            ['amount' => 10, 'price' => 0],
        ]));
        $this->assertFalse($product->validateBulkOptions([
            ['amount' => 1, 'price' => 10],
            ['amount' => 2, 'price' => 20],
            ['amount' => 3, 'price' => 30],
            ['amount' => 4, 'price' => 40],
            ['amount' => 5, 'price' => 50],
        ]));
    }

    #[DataProvider('stockAvailabilityProvider')]
    public function test_cart_stock_validation_accounts_for_bulk_options(
        int $stock,
        int $quantity,
        ?array $bulkOption,
        bool $expectedValidity
    ): void {
        $product = new Product(['stock_amount' => $stock]);

        $result = Cart::validateStockAvailability($product, $quantity, $bulkOption);

        $this->assertSame($expectedValidity, $result['valid']);
    }

    public static function stockAvailabilityProvider(): array
    {
        return [
            'regular quantity within stock' => [10, 3, null, true],
            'regular quantity beyond stock' => [10, 11, null, false],
            'bulk sets within stock' => [10, 2, ['amount' => 5], true],
            'bulk sets beyond stock' => [10, 3, ['amount' => 5], false],
        ];
    }
}
