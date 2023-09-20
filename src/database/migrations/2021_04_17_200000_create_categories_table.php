<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id');
            $table->foreignId('user_id');
            $table->foreignId('client_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('account_id')->nullable();
            $table->foreignId('resource_type_id')->nullable();

            $table->string('color')->nullable();
            $table->text('icon')->nullable();
            $table->string('resource_type', 100)->nullable();
            $table->string('display_id');
            $table->integer('number')->nullable();
            $table->string('name', 100);
            $table->string('alias')->nullable();
            $table->text('description')->nullable();
            $table->integer('type')->default(1);
            $table->integer('index')->default(0);
            $table->integer('depth')->default(0);
            $table->enum('status', ['disabled','active'])->default('active');
            $table->json('meta_data')->nullable('{}');
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
        Schema::dropIfExists('categories');
    }
}
