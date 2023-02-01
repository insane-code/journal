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
        Schema::create('expense_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('invoice_id');
            $table->foreignId('customer_id');
            $table->string('customer_name');
            $table->foreignId('payment_account_id');
            $table->string('payment_account_name');

            $table->boolean('is_personal')->default(false);
            $table->boolean('is_billable')->default(true);
            $table->enum('status', ['unbilled','billed'])->default('unbilled');

            // structure
            $table->timestamp('deleted_at')->nullable();
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
        Schema::dropIfExists('expense_details');
    }
};
