<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->float('credit')->nullable();
            $table->float('discount')->nullable();
            $table->float('tips')->nullable();
            $table->float('tax')->nullable();
            $table->float('sub_total');
            $table->float('total');
            $table->string('payment_mode');
            $table->string('currency')->nullable();
            $table->integer('created_by_id')->nullable();
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
        Schema::dropIfExists('payments');
    }
}
