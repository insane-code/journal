<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsPricesTable extends Migration
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
            $table->foreignId('product_type_id');
            $table->foreignId('category_id');

            // content
            $table->string('name');
            $table->string('slug');
            $table->string('sku');
            $table->text('description');
            $table->text('descriptionHTML');
            $table->integer('weight');
            $table->boolean('available');
            //
            // $table->decimal('price', 11, 2)->default(0.00);
            // $table->decimal('variants', 11, 2)->default(0.00);
            // $table->decimal('product Option, 11, 2)->default(0.00);
            // $table->decimal('images, 11, 2)->default(0.00);

            // secondary


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
        Schema::dropIfExists('transaction_lines');
    }
}
