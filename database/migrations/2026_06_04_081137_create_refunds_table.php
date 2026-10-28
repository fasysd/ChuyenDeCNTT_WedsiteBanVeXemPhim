<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', [
                'PENDING',
                'COMPLETED',
                'FAILED'
            ])->default('PENDING');
            $table->string('refund_code')->unique();
            $table->string('transaction_code')->nullable();
            $table->string('provider_reference')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
