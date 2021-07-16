<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanySupplierTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('company_supplier', function($table)
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->unsigned()->index();
			$table->integer('supplier_id')->unsigned()->index();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('company_supplier');
	}

}
