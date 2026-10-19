<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'idempotency_key',
        'customer_id',
        'collector_id',
        'paid_at',
        'amount_received',
        'payment_method',
        'status',
        'notes',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_received' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function totalAllocated(): int
    {
        return (int) $this->allocations()->sum('amount_allocated');
    }

    public function isCancelled(): bool
    {
        return $this->status === PaymentStatus::Cancelled;
    }
}
