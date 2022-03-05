<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryAdjustmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('reference_no')->nullable();
            $table->string('adjustment_type')->nullable();
            $table->integer('account_id')->nullable();
            $table->text('description')->nullable();
            $table->string('reason')->nullable();
            $table->float('current_value')->nullable();
            $table->float('changed_value')->nullable();
            $table->string('adjustment_value')->nullable();
            $table->float('quantity_available')->nullable();
            $table->float('quantity_on_hand')->nullable();
            $table->string('adjusted_quantity_value')->nullable();
            $table->float('purchase_price')->nullable();
            $table->float('cost_price')->nullable();
            $table->integer('status')->default(0);
            $table->integer('organization_id')->nullable();
            $table->integer('created_by_id')->nullable();
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
        Schema::dropIfExists('inventory_adjustments');
    }
}
