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
        Schema::create('quote_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('quote_id');
            $table->foreignId('quote_line_id');
            $table->foreignId('tax_id');
            $table->integer('index')->default(0);
            $table->string('name');
            $table->string('label')->nullable();
            $table->string('concept')->nullable();
            $table->decimal('amount', 11, 4);
            $table->decimal('amount_base', 11, 4);
            $table->decimal('rate', 11, 2)->default(0.00);
            $table->integer('type')->default(1);
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
        Schema::dropIfExists('quote_line_taxes');
    }
};
