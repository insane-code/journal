<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('transaction_id');
            $table->foreignId('payee_id')->nullable();

            // content
            $table->foreignId('account_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->date('date');
            $table->integer('type')->default(1)->comment("1 debit, -1 credit");
            $table->text('concept', 300);
            $table->decimal('amount', 11, 2)->default(0.00);
            $table->integer('index')->nullable();
            $table->boolean('anchor')->default(0);
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
