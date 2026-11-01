<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When 0.x was merged into master, some column changes were folded into the
 * create migrations, which 0.x databases ran before the change existed.
 * Re-apply them so both kinds of database end up with the same schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('description', 200)->nullable()->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('type', ['INVOICE', 'EXPENSE', 'CREDIT_NOTE', 'DEBIT_NOTE'])->default('INVOICE')->change();
        });
    }

    public function down(): void
    {
        //
    }
};
