<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductOutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_outs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade')->onUpdate('cascade');
            $table->bigInteger('analyzer_id')->unsigned();
            $table->foreign('analyzer_id')->references('id')->on('analyzers')->onDelete('cascade')->onUpdate('cascade');
            $table->string('stock')->nullable();
            $table->string('qty')->nullable();
            $table->bigInteger('test')->nullable();
            $table->string('usage')->nullable();
            $table->string('remainder_test')->nullable();
            $table->string('remainder_stock')->nullable();
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
        Schema::dropIfExists('product_outs');
    }
}
