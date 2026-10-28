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
        Schema::create('ticket_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('showtime_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('seat_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->decimal('final_price', 10, 2);
            $table->enum('status', [
                'HOLDING',
                'BOOKED',
                'USED',
                'CANCELLED'
            ])->default('HOLDING');
            $table->timestamps();
            $table->unique([
                'showtime_id',
                'seat_id'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_details');
    }
};
