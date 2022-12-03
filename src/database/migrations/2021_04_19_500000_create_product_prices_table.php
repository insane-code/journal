<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('product_id')->nullable();
            $table->foreignId('products_variant_id')->nullable();

            // content
            $table->decimal('value', 11, 2)->default(0);
            $table->string('name')->default('Default');
            $table->string('currency')->default('DOP');
            $table->decimal('retail_price', 11, 2)->nullable();
            $table->decimal('sale_price', 11, 2)->nullable();
            $table->decimal('list_price', 11, 2)->nullable();
            $table->decimal('extended_sale_price', 11, 2)->nullable();
            $table->decimal('extended_list_price', 11, 2)->nullable();
            $table->boolean('is_main')->default(false);

            // structure
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products_prices');
    }
};
