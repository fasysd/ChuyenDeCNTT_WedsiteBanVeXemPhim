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
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('seat_row', 2);
            $table->integer('seat_number');
            $table->enum('type', [
                'STANDARD',
                'VIP'
            ])->default('STANDARD');
            $table->timestamps();
            $table->unique([
                'room_id',
                'seat_row',
                'seat_number'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
