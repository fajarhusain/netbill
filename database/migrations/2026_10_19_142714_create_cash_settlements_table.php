<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->date('settlement_date');
            $table->unsignedInteger('expected_amount')->default(0);
            $table->unsignedInteger('received_amount')->default(0);
            $table->integer('difference_amount')->default(0);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('confirmed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['collector_id', 'settlement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_settlements');
    }
};
