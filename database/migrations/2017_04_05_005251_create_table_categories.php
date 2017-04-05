<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rec_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('rec_recipes_categories', function (Blueprint $table) {
            $table->integer('recipe_id')->unsigned();
            $table->foreign('recipe_id')->references('id')->on('rec_recipes');
            $table->integer('categorie_id')->unsigned();
            $table->foreign('categorie_id')->references('id')->on('rec_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rec_recipes_categories');
        Schema::dropIfExists('rec_categories');
    }
}
