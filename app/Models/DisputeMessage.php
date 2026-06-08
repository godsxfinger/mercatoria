<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DisputeMessage extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'dispute_id',
        'user_id',
        'message',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::random(30);
            }
        });
    }

    public function dispute()
    {
        return $this->belongsTo(Dispute::class, 'dispute_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isFromAdmin()
    {
        return $this->user && $this->user->hasRole('admin');
    }

    public function isFromBuyer()
    {
        if (!$this->user || !$this->dispute || !$this->dispute->order) {
            return false;
        }

        return $this->user->id === $this->dispute->order->user_id;
    }

    public function isFromVendor()
    {
        if (!$this->user || !$this->dispute || !$this->dispute->order) {
            return false;
        }

        return $this->user->id === $this->dispute->order->vendor_id;
    }

    public function getMessageType()
    {
        if ($this->isFromAdmin()) {
            return 'admin';
        }

        if ($this->isFromBuyer()) {
            return 'buyer';
        }

        if ($this->isFromVendor()) {
            return 'vendor';
        }

        return 'unknown';
    }
}
