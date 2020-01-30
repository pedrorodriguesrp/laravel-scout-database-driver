<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSearchablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('searchables', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('searchable_id');
            $table->string('searchable_model');
            $table->text('searchable_data');
            $table->timestamps();
            $table->index(['searchable_id']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('searchables');
    }
}
