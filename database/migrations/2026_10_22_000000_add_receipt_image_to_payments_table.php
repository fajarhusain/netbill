<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_image_path')->nullable()->after('notes');
            $table->datetime('receipt_sent_at')->nullable()->after('receipt_image_path');
            $table->foreignId('receipt_sent_by')->nullable()->after('receipt_sent_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receipt_sent_by');
            $table->dropColumn(['receipt_image_path', 'receipt_sent_at']);
        });
    }
};
