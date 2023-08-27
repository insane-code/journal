<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('invoice_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreignId('team_id');
            $table->foreignId('invoice_id');
            $table->foreignId('note_id');

            $table->string('type');
            $table->date('date');
            $table->decimal('amount', 11, 2);
            $table->integer('number')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        DB::statement("ALTER TABLE invoices MODIFY COLUMN type ENUM('INVOICE','EXPENSE', 'CREDIT_NOTE', 'DEBIT_NOTE')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_relations');
    }
};
