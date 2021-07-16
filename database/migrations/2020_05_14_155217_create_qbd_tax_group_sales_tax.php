<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdTaxGroupSalesTax extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_tax_group_sales_tax', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('group_id');
			$table->integer('tax_id');
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
		Schema::drop('qbd_tax_group_sales_tax');
	}

}
