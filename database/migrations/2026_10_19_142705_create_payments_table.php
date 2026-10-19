<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 30)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->datetime('paid_at');
            $table->unsignedInteger('amount_received');
            $table->enum('payment_method', ['cash', 'transfer', 'qris'])->default('cash');
            $table->enum('status', ['success', 'cancelled'])->default('success');
            $table->text('notes')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index(['collector_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('payment_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
