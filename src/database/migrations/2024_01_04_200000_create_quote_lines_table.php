<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('quote_id');
            $table->foreignId('product_id')->nullable();
            
            // context
            $table->date('date');
            $table->text('concept', 200);
            $table->text('product_image')->nullable();
            $table->json('meta_data')->nullable();    
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
        Schema::dropIfExists('quote_lines');
    }
};
