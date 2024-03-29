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
            $table->foreignId('payment_document_id')->nullable();

            $table->foreignId('payable_id');
            $table->string('payable_type');

            $table->foreignId('account_id');
            $table->string('account_name')->nullable();
            $table->foreignId('payment_method_id')->nullable();
            $table->string('payment_method')->nullable();

            $table->date('payment_date');
            $table->integer('number')->nullable();
            $table->date('document_date')->nullable();
            $table->decimal('amount', 11, 2);
            $table->string('concept', 50);
            $table->string('reference', 200)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('checked')->default(false);
            $table->json('documents')->default('[]');
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
