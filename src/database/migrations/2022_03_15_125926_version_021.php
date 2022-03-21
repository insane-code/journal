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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('income_account_id')->nullable()->after('category_id');
            $table->foreignId('expense_account_id')->nullable()->after('income_account_id');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
        });

        Schema::create('product_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('product_id');
            $table->foreignId('tax_id');
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
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('category_id');
            $table->dropColumn('income_account_id');
            $table->dropColumn('expense_account_id');
        });

        Schema::dropIfExists('product_taxes');
    }
};
