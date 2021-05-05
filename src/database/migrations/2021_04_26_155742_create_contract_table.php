<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->foreignId('client_id');
            $table->foreignId('project_id')->nullable();
            $table->foreignId('user_signature_id')->nullable();
            $table->foreignId('client_signature_id')->nullable();

            $table->foreignId('country_id')->nullable();
            $table->string('job_title');
            $table->boolean('is_through_company')->default(false);
            $table->string('company_name')->nullable();
            $table->string('company_job_title')->nullable();
            $table->boolean('is_company')->default(true);
            $table->string('client_name');

            $table->text('scope_description');
            $table->enum('payment_type', ['flat_fee', 'milestone', 'hourly_rate', 'daily_rate', 'weekly_rate', 'monthly_rate', 'per_word_rate']);
            $table->decimal('amount', 11, 2);
            $table->string('currency', 3)->default('DOP');
            $table->text('notes');
            $table->decimal('deposit_amount', 11, 2);

            $table->integer('due_days')->default(15);
            $table->integer('penalty_percent')->default(5);
            $table->date('start_date');
            $table->date('end_date')->nullable();

            $table->decimal('early_end_amount', 11, 2)->nullable();
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
        Schema::dropIfExists('payments');
    }
}
