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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol', 10);
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 20, 8);
            $table->decimal('amount', 20, 8);
            $table->tinyInteger('status')->default(1)->comment('1=open, 2=filled, 3=cancelled');
            $table->decimal('locked_usd', 20, 8)->default(0)->comment('For buy orders: amount * price');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['symbol', 'side', 'status', 'price', 'created_at'], 'idx_orders_matching');
            $table->index(['status', 'symbol', 'side', 'price', 'created_at'], 'idx_orders_orderbook');
            $table->index('price', 'idx_orders_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
