<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQbdTransactionUpdatedTime extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('qbd_transaction_updated_time', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->string('type');
			$table->dateTime('object_last_updated');
			$table->integer('jp_object_id');
			$table->string('qb_desktop_txn_id');
			$table->string('qb_desktop_sequence_number');
			$table->dateTime('txn_date');
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
		Schema::drop('qbd_transaction_updated_time');
	}

}
