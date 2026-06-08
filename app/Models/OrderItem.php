<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_description',
        'price',
        'quantity',
        'measurement_unit',
        'delivery_option',
        'bulk_option',
        'delivery_text',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'delivery_option' => 'array',
        'bulk_option' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getTotalPrice()
    {
        $basePrice = $this->price * $this->quantity;
        $deliveryPrice = (float) ($this->delivery_option['price'] ?? 0);

        return $basePrice + $deliveryPrice;
    }

    public function getFormattedDeliveryOption()
    {
        if (!$this->delivery_option) {
            return null;
        }

        return [
            'description' => $this->delivery_option['description'] ?? 'N/A',
            'price' => isset($this->delivery_option['price'])
                ? '$' . number_format($this->delivery_option['price'], 2)
                : 'N/A',
        ];
    }

    public function getFormattedBulkOption()
    {
        if (!$this->bulk_option) {
            return null;
        }

        $unit = $this->measurement_unit;
        $formattedUnit = Product::getMeasurementUnits()[$unit] ?? $unit;

        return [
            'amount' => $this->bulk_option['amount'] ?? 0,
            'price' => isset($this->bulk_option['price'])
                ? '$' . number_format($this->bulk_option['price'], 2)
                : 'N/A',
            'display_text' => sprintf(
                '%s %s for $%s',
                number_format($this->bulk_option['amount'] ?? 0),
                $formattedUnit,
                number_format($this->bulk_option['price'] ?? 0, 2)
            ),
        ];
    }
}
