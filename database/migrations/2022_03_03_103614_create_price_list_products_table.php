<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceListProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_list_products', function (Blueprint $table) {
            $table->id();
            $table->integer('price_list_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->string('reference')->nullable();
            $table->float('sales_rate');
            $table->float('custom_rate');
            $table->float('discount_percent')->nullable();
            $table->string('currency')->nullable();
            $table->integer('organization_id')->nullable();
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
        Schema::dropIfExists('price_list_products');
    }
}
