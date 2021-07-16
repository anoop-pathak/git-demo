<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQboBillsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbo_bills', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('qb_id')->nullable();
			$table->integer('qb_vendor_id')->nullable();
			$table->integer('qb_customer_id')->nullable();
			$table->float('total_amount', 10, 2)->nullable();
			$table->dateTime('due_date');
			$table->dateTime('qb_creation_date');
			$table->dateTime('qb_modified_date');
			$table->text('meta')->nullable();
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
		Schema::drop('qbo_bills');
	}

}
