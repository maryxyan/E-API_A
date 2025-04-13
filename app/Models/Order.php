<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Validation errors array
     *
     * @var array
     */
    protected $errors = [];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'total_price' => 'required|numeric|min:0',
        'status' => 'required|in:pending,processing,completed,cancelled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the order can be updated to a new status.
     */
    public function canUpdateStatus(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => [self::STATUS_COMPLETED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [], // Prevent transition from PROCESSING to CANCELLED
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    /**
     * Calculate the total price of the order.
     */
    public function calculateTotalPrice(): float
    {
        return $this->orderItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    /**
     * Update the order status.
     */
    public function updateStatus(string $status): bool
    {
        if (!$this->canUpdateStatus($status)) {
            return false;
        }

        $this->status = $status;
        return $this->save();
    }

    /**
     * Validate the order and its items
     */
    public function validate(): bool
    {
        $errors = []; // Use local array first
        $validator = \Validator::make($this->attributes, static::$rules);
        
        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
        }

        // Validate order items if present
        if ($this->orderItems && $this->orderItems->isNotEmpty()) {
            foreach ($this->orderItems as $index => $item) {
                $itemValidator = \Validator::make(
                    $item->attributes, 
                    OrderItem::$rules
                );

                if ($itemValidator->fails()) {
                    foreach ($itemValidator->errors()->toArray() as $key => $error) {
                        $errors["orderItems.$index.$key"] = $error;
                    }
                }
            }
        }

        $this->errors = $errors; // Assign to property at the end
        return empty($errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors ?? [];
    }
}
