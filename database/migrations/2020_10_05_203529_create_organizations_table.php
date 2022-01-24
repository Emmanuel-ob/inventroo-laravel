<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrganizationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clientName');
            $table->string('business_email', 70)->unique();
            $table->string('description')->nullable();
            $table->string('country')->nullable();
            $table->string('address');
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('business_phone_no')->nullable();
            $table->string('account_type')->nullable();
            $table->integer('admin_user_id')->nullable();
            $table->integer('sub_user_count')->nullable();
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
        Schema::dropIfExists('organizations');
    }
}
