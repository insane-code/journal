<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoice extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('client_id');
            $table->foreignId('account_id')->nullable();
            $table->foreignId('invoice_account_id')->nullable();
            $table->foreignId('invoiceable_id')->nullable();
            $table->string('invoiceable_type');

            // content

            $table->string('series', 10);
            $table->integer('number');
            $table->date('date');
            $table->date('due_date');

            // header
            $table->string('concept', 50);
            $table->string('description', 200);
            $table->text('logo')->nullable();

            // footer
            $table->text('notes')->nullable();
            $table->text('footer')->nullable();

            // totals
            $table->decimal('subtotal', 11, 2)->default(0.00);
            $table->decimal('penalty', 11, 2)->default(0.00);
            $table->decimal('extra_amount', 11, 2)->default(0.00);
            $table->decimal('discount', 11, 2)->default(0.00);
            $table->decimal('total', 11, 2)->default(0.00);
            $table->decimal('debt', 11, 2)->default(0.00);
            $table->enum('type', ['INVOICE','EXPENSE'])->default('INVOICE');
            $table->enum('status', ['draft','unpaid','partial', 'paid', 'canceled', 'overdue'])->default('draft');
            // structure
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
        Schema::dropIfExists('invoices');
    }
}
