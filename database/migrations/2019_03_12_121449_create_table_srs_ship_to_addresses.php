<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTableSrsShipToAddresses extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('srs_ship_to_addresses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('company_supplier_id');
			$table->string('ship_to_id');
			$table->string('ship_to_sequence_id');
			$table->string('city');
			$table->string('state');
			$table->string('zip_code');
			$table->string('address_line1')->nullable();
			$table->string('address_line2')->nullable();
			$table->string('address_line3')->nullable();
			$table->text('meta')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('srs_ship_to_addresses');
	}

}
