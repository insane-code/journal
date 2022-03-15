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
            $table->text('order_number')->nullable()->after('number');
            $table->string('currency_code')->default("DOP")->after('total');
            $table->decimal('currency_rate', 11, 4)->default(1)->after('currency_code');
            $table->string('contact_name')->nullable()->after('description');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_tax_number')->nullable()->after('contact_email');
            $table->string('contact_phone')->nullable()->after('contact_tax_number');
            $table->string('contact_address')->nullable()->after('contact_phone');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
        });

        Schema::create('invoice_line_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('invoice_id');
            $table->foreignId('invoice_line_id');
            $table->foreignId('tax_id');
            $table->integer('index')->default(0);
            $table->string('name');
            $table->decimal('amount', 11, 4);
            $table->decimal('amount_base', 11, 4);
            $table->timestamps();
        });

        Schema::create('invoice_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id');
            $table->foreignId('invoice_id');
            $table->string('status');
            $table->boolean('notify');
            $table->timestamps();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('account_id',)->after('resource_id');
            $table->foreignId('category_id')->nullable()->after('account_id');
            $table->decimal('currency_rate', 11, 4)->default(1)->after('currency_code');
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
            $table->dropColumn('order_number');
            $table->dropColumn('currency_code');
            $table->dropColumn('currency_rate');
            $table->dropColumn('contact_name');
            $table->dropColumn('contact_email');
            $table->dropColumn('contact_tax_number');
            $table->dropColumn('contact_phone');
            $table->dropColumn('contact_address');
            $table->dropColumn('deleted_at');
        });

        Schema::dropIfExists('invoice_line_taxes');
        Schema::dropIfExists('invoice_logs');
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('currency_rate');
        });
    }
};
