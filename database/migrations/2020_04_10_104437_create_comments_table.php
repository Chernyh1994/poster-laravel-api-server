<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
              $table->increments('id');
              $table->integer('author_id')->unsigned();
              $table->integer('post_id')->unsigned();
              $table->integer('parent_id')->unsigned()->nullable();
              $table->text('content');
              $table->timestamps();

              $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
              $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
              $table->foreign('parent_id')->references('id')->on('comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
}
