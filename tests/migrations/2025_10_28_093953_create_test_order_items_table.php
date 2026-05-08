<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('mollie_test_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id');
            $table->morphs('buyable');
            $table->string('name')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 15, 4);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mollie_test_order_items');
    }
};
