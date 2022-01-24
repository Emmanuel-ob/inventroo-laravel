<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('bvn')->nullable();
            $table->string('unique_id')->nullable();
            $table->string('verification_ref')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('email_verified')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->integer('status')->default(0);
            $table->string('street')->nullable();
            $table->string('street2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('phone')->nullable();
            $table->string('account_type')->nullable();
            $table->string('gender')->nullable();
            $table->text('profile_image_link')->nullable();
            $table->integer('branch_id')->nullable();
            $table->integer('organization_id')->nullable();
            $table->integer('otp')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}
