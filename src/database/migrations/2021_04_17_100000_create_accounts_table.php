<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            // structure data
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('client_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->foreignId('account_detail_type_id')->default(1);
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('tax_id')->nullable();
            
            // Basic
            $table->string('display_id');
            $table->integer('number')->nullable();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->text('description')->nullable();
            $table->string('currency_code', 4)->default("DOP");
            $table->integer('index')->default(0);
            $table->decimal('opening_balance', 11, 2)->default(0);
            $table->decimal('current_balance', 11, 2)->default(0);

            // direction
            $table->boolean('archivable')->default(0);
            $table->boolean('archived')->default(1);
            $table->integer('type')->default(1);
            $table->enum('balance_type', ['DEBIT','CREDIT'])->default('DEBIT');
            $table->enum('status', ['disabled','active'])->default('active');

            // state
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
        Schema::dropIfExists('accounts');
    }
}
