<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdItemSalesTaxGroups extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_item_sales_tax_groups', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('qb_username');
			$table->string('name');
			$table->string('description');
			$table->tinyInteger('active');
			$table->string('qb_desktop_id')->nullable();
			$table->string('qb_desktop_sequence_number')->nullable();
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
		Schema::drop('qbd_item_sales_tax_groups');
	}

}
