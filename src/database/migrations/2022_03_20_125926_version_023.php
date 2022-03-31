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
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('taxes_included')->default(false);
        });

        Schema::table('invoice_line_taxes', function (Blueprint $table) {
            $table->decimal('rate', 11, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('taxes_included');
        });

        Schema::table('invoice_line_taxes', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
};
