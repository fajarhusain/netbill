<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'barcode_token',
        'name',
        'phone',
        'address',
        'dusun',
        'package_id',
        'monthly_fee',
        'started_at',
        'due_day',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'monthly_fee' => 'integer',
            'due_day' => 'integer',
            'status' => CustomerStatus::class,
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function waBlastLogs(): HasMany
    {
        return $this->hasMany(WaBlastLog::class);
    }

    public function collectionVisits(): HasMany
    {
        return $this->hasMany(CollectionVisit::class);
    }

    public function activeInvoices(): HasMany
    {
        return $this->invoices()->whereIn('status', [
            InvoiceStatus::Unpaid,
            InvoiceStatus::Partial,
        ]);
    }

    public function totalArrears(): int
    {
        return (int) $this->activeInvoices()->sum('amount') - (int) $this->activeInvoices()->sum('paid_amount');
    }
}
