<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Dispute extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    // Dispute status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_VENDOR_PREVAILS = 'vendor_prevails';
    public const STATUS_BUYER_PREVAILS = 'buyer_prevails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'order_id',
        'status',
        'reason',
        'resolved_at',
        'resolved_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            // Set 30-character alphanumeric string if not set
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::random(30);
            }
        });
    }

    /**
     * Get the order that owns the dispute.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the admin user who resolved the dispute.
     */
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the messages for this dispute.
     */
    public function messages()
    {
        return $this->hasMany(DisputeMessage::class, 'dispute_id')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get the formatted status.
     */
    public function getFormattedStatus()
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Active Dispute',
            self::STATUS_VENDOR_PREVAILS => 'Vendor Prevails',
            self::STATUS_BUYER_PREVAILS => 'Buyer Prevails',
            default => 'Unknown Status'
        };
    }

    /**
     * Resolve the dispute with vendor prevailing.
     */
    public function resolveVendorPrevails($adminId)
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Complete the order first; dispute can only be resolved if order transition succeeds.
        if (!$this->order->markAsCompleted()) {
            return false;
        }

        $this->status = self::STATUS_VENDOR_PREVAILS;
        $this->resolved_at = now();
        $this->resolved_by = $adminId;
        $this->save();

        return true;
    }

    /**
     * Resolve the dispute with buyer prevailing.
     */
    public function resolveBuyerPrevails($adminId)
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        // Cancel the order first; dispute can only be resolved if order transition succeeds.
        if (!$this->order->markAsCancelled()) {
            return false;
        }

        $this->status = self::STATUS_BUYER_PREVAILS;
        $this->resolved_at = now();
        $this->resolved_by = $adminId;
        $this->save();

        return true;
    }

    /**
     * Get all disputes for the admin.
     */
    public static function getAllDisputes()
    {
        return self::with(['order', 'order.user', 'order.vendor'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get active disputes for the admin.
     */
    public static function getActiveDisputes()
    {
        return self::where('status', self::STATUS_ACTIVE)
            ->with(['order', 'order.user', 'order.vendor'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get resolved disputes for the admin.
     */
    public static function getResolvedDisputes()
    {
        return self::whereIn('status', [self::STATUS_VENDOR_PREVAILS, self::STATUS_BUYER_PREVAILS])
            ->with(['order', 'order.user', 'order.vendor', 'resolver'])
            ->orderBy('resolved_at', 'desc')
            ->get();
    }

    /**
     * Get all disputes for a user (as buyer).
     */
    public static function getUserDisputes($userId)
    {
        return self::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with(['order', 'order.vendor'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Get all disputes for a vendor.
     */
    public static function getVendorDisputes($vendorId)
    {
        return self::whereHas('order', function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId);
        })
        ->with(['order', 'order.user'])
        ->orderBy('created_at', 'desc')
        ->get();
    }
}

