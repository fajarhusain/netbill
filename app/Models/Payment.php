<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
        'receipt_image_path',
        'receipt_sent_at',
        'receipt_sent_by',
        'receipt_share_token',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount_received' => 'integer',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'cancelled_at' => 'datetime',
            'receipt_sent_at' => 'datetime',
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

    public function receiptSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receipt_sent_by');
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

    public function ensureReceiptShareToken(): string
    {
        if (! $this->receipt_share_token) {
            $this->forceFill(['receipt_share_token' => Str::random(32)])->save();
        }

        return $this->receipt_share_token;
    }

    public function publicReceiptUrl(): ?string
    {
        if (! $this->receipt_share_token) {
            return null;
        }

        return route('payments.receipt.public', $this->receipt_share_token);
    }

    public function whatsappNumber(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->customer?->phone ?? '');

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone;
    }

    public function whatsappReceiptUrl(?string $imageUrl = null): ?string
    {
        $phone = $this->whatsappNumber();

        if ($phone === '') {
            return null;
        }

        $text = "Bukti Pembayaran JRC Media\n"
            ."No: {$this->transaction_number}\n"
            .'Nama: '.($this->customer?->name ?? '-')."\n"
            .'Total: '.format_rupiah($this->amount_received)."\n"
            .'Metode: '.$this->payment_method->label()."\n"
            .'Petugas: '.($this->collector?->name ?? '-')."\n"
            .'Tanggal: '.format_datetime($this->paid_at);

        if ($imageUrl) {
            $text .= "\nBukti: ".$imageUrl;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}
