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
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('invoice_id');
            $table->foreignId('product_id')->nullable();

            // content
            $table->text('concept', 200);
            $table->decimal('price', 11, 2)->default(0.00);
            $table->decimal('quantity', 11, 2)->default(0.00);
            $table->decimal('discount', 11, 2)->default(0.00);
            $table->decimal('amount', 11, 2)->default(0.00);
            $table->integer('index')->nullable();
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
        Schema::dropIfExists('invoice_lines');
    }
};
