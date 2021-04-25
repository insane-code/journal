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
            $table->foreignId('resource_type_id')->nullable();

            $table->string('resource_type', 100)->nullable();
            $table->string('display_id');
            $table->string('name', 100);
            $table->string('description', 200)->nullable();
            $table->integer('index')->default(0);
            $table->integer('depth')->default(0);
            $table->enum('status', ['disabled','active'])->default('active');
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
