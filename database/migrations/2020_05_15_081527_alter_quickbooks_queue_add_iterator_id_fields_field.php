<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksQueueAddIteratorIdFieldsField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->string('iterator_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->dropColumn('iterator_id');
		});
	}
}
