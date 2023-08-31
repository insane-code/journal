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
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id');
            $table->foreignId('account_id');

            $table->string('type');
            $table->date('date');
            $table->decimal('amount', 11, 2);
            $table->decimal('difference', 11, 2);
            $table->string('status', 11, 2);
            $table->timestamps();
        });

        Schema::create('reconciliation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id');
            $table->foreignId('reconciliation_id');
            $table->foreignId('transaction_id');
            $table->foreignId('transaction_line_id');
            $table->boolean('matched')->default(false);
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
        Schema::dropIfExists('reconciliations');
        Schema::dropIfExists('reconciliation_entries');
    }
};
