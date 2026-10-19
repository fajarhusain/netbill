<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->datetime('visited_at');
            $table->enum('result', [
                'paid',
                'not_home',
                'recall_requested',
                'cannot_pay',
                'address_not_found',
                'other',
            ])->default('other');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['collector_id', 'visited_at']);
            $table->index(['customer_id', 'visited_at']);
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_visits');
    }
};
