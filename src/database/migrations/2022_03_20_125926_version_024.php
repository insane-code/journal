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
        Schema::create('account_detail_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->text('Description');
            $table->json('config');
            $table->timestamps();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('account_detail_type_id')->default(1);
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('tax_id')->nullable();
            $table->decimal('opening_balance', 11, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_detail_types');
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('account_detail_type_id');
            $table->dropColumn('parent_id');
            $table->dropColumn('tax_id');
            $table->dropColumn('opening_balance');
        });
    }
};
