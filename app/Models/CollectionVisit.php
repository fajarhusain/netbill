<?php

namespace App\Models;

use App\Enums\VisitResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'collector_id',
        'visited_at',
        'result',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'result' => VisitResult::class,
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
}
