<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('reg_no')->nullable();
            $table->string('vat_id')->nullable();
            $table->string('business_category')->nullable();
            $table->string('website_link')->nullable();
            $table->string('biz_acct_no')->nullable();
            $table->string('biz_acct_name')->nullable();
            $table->string('biz_acct_bank')->nullable();
            $table->string('biz_acct_country')->nullable();
            $table->timestamp('fiscal_year_from')->nullable();
            $table->timestamp('fiscal_year_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('reg_no', 'vat_id', 'business_category', 'website_link', 'fiscal_year_from', 'fiscal_year_to', 'biz_acct_no', 'biz_acct_name', 'biz_acct_bank', 'biz_acct_country');
        });
    }
}
