<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('client_id');

            $table->foreignId('payable_id');
            $table->string('payable_type');

            $table->foreignId('payment_method_id')->nullable();
            $table->foreignId('account_id');
      
            $table->date('payment_date');
            $table->decimal('amount', 11, 2);
            $table->string('concept', 50);
            $table->text('notes')->nullable();
            
            $table->boolean('checked')->default(false);
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
        Schema::dropIfExists('payments');
    }
}
