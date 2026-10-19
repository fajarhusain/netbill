<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'billing_period',
        'issued_at',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_date' => 'date',
            'amount' => 'integer',
            'paid_amount' => 'integer',
            'status' => InvoiceStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function payments()
    {
        return $this->belongsToMany(Payment::class, 'payment_allocations')
            ->withPivot('amount_allocated')
            ->withTimestamps();
    }

    public function balance(): int
    {
        return max(0, (int) $this->amount - (int) $this->paid_amount);
    }

    public function isSettled(): bool
    {
        return $this->status === InvoiceStatus::Paid;
    }

    public function scopeUnsettled(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);
    }

    public function scopeArrears(Builder $query): Builder
    {
        return $query->unsettled()->whereColumn('paid_amount', '<', 'amount');
    }
}
