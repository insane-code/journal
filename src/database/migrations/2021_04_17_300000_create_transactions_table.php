<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            // basic data
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('resource_id')->nullable();
            $table->foreignId('transactionable_id')->nullable();
            $table->foreignId('resource_type_id')->nullable();
            $table->foreignId('account_id')->nullable();
            $table->foreignId('counter_account_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('payee_id')->nullable();
            $table->string('payeable_type')->nullable();

            $table->integer('number');
            $table->date('date');

            // header
            $table->string('transactionable_type')->nullable();
            $table->string('description', 200);
            $table->enum('direction', ['DEPOSIT','WITHDRAW'])->default('DEPOSIT');

            // footer
            $table->text('notes')->nullable();

            // totals
            $table->string('currency_code', 3)->default('DOP');
            $table->decimal('currency_rate', 11, 4)->default(1);
            $table->decimal('total', 11, 2)->default(0.00);
            $table->enum('status', ['draft','planned', 'verified', 'canceled'])->default('draft');
            $table->boolean('is_transfer')->default(false);
            $table->json('meta_data')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
