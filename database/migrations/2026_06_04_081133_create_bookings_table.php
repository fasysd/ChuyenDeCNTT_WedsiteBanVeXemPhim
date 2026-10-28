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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('total_price', 10, 2);
            $table->enum('status', [
                'PENDING',
                'PAID',
                'CANCELLED',
                'REFUNDED'
            ])->default('PENDING');
            $table->timestamp('holding_expires_at')->nullable();
            $table->timestamp('expired_at')->nullable(); // Dự phòng tương thích
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
