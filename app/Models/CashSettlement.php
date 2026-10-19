<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'collector_id',
        'settlement_date',
        'expected_amount',
        'received_amount',
        'difference_amount',
        'confirmed_by',
        'confirmed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'settlement_date' => 'date',
            'expected_amount' => 'integer',
            'received_amount' => 'integer',
            'difference_amount' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
