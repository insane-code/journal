<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
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
            $table->string('invoiceable_type')->nullable();
            $table->foreignId('refund_id')->nullable();

            // content
            $table->string('series', 10);
            $table->integer('number');
            $table->text('order_number')->nullable();
            $table->date('date');
            $table->date('due_date');

            // header
            $table->string('concept', 50);
            $table->string('description', 200);
            $table->text('logo')->nullable();
            // contact information
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_tax_number')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_address')->nullable();

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
            $table->string('currency_code')->default("DOP");
            $table->decimal('currency_rate', 11, 4)->default(1);
            $table->boolean('taxes_included')->default(false);

            $table->string('category_type')->nullable();
            $table->enum('type', ['INVOICE','EXPENSE', 'CREDIT_NOTE', 'DEBIT_NOTE'])->default('INVOICE');
            $table->enum('status', ['draft','unpaid','partial', 'paid', 'canceled', 'overdue'])->default('draft');

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
        Schema::dropIfExists('invoices');
    }
};
