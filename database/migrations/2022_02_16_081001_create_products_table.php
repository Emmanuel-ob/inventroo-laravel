<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->integer('status')->default(1);
            $table->string('type');
            $table->string('dimension')->nullable();
            $table->integer('unit_id')->nullable();
            $table->integer('brand_id')->nullable();
            $table->integer('manufacturer_id')->nullable();
            $table->integer('tax_id')->nullable();
            $table->integer('organization_id')->nullable();
            $table->string('upc')->nullable();
            $table->string('mpn')->nullable();
            $table->string('ean')->nullable();
            $table->string('isbn')->nullable();
            $table->string('currency')->nullable();
            $table->float('sale_price');
            $table->float('sale_tax_percent')->nullable();
            $table->float('cost_price');
            $table->float('cost_tax_percent')->nullable();
            $table->integer('inventory_account_id')->nullable();
            $table->float('opening_stock')->nullable();
            $table->float('opening_stock_rate_per_unit')->nullable();
            $table->string('recorder_point')->nullable();
            $table->string('prefered_vendor')->nullable();
            $table->text('image_link')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('products');
    }
}
