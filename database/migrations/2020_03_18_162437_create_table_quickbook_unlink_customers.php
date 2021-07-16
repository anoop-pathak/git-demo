<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableQuickbookUnlinkCustomers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbook_unlink_customers', function(Blueprint $table) {
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('quickbook_id')->index();
			$table->integer('created_by')->nullable();
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
		Schema::dropIfExists('quickbook_unlink_customers');
	}

}
