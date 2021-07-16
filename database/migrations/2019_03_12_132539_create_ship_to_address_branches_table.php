<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShipToAddressBranchesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ship_to_address_branches', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('supplier_branch_id');
			$table->integer('srs_ship_to_address_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ship_to_address_branches');
	}

}
