<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('product_type_id')->nullable();
            $table->foreignId('category_id')->nullable();

            // content
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('sku');
            $table->text('description');
            $table->text('descriptionHTML')->nullable();
            $table->integer('weight')->nullable();
            $table->boolean('available')->default(1);
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
        Schema::dropIfExists('products');
    }
}
