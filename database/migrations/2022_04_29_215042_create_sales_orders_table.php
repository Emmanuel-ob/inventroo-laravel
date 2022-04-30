<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->string('customer_name')->nullable();
            $table->text('sales_order')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('sales_date')->nullable();
            $table->timestamp('expected_shipment_date')->nullable();
            $table->string('payment_term')->nullable();
            $table->text('delivery_method')->nullable();
            $table->text('customer_note')->nullable();
            $table->string('sales_person')->nullable();
            $table->float('total')->nullable();
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
        Schema::dropIfExists('sales_orders');
    }
}
