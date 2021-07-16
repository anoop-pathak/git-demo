<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQuickbooksQueueAddTwoWaySyncFieldsFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quickbooks_queue', function(Blueprint $table)
		{
			$table->string('action')->nullable();
			$table->string('object')->nullable();
			$table->string('object_id')->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->tinyInteger('origin')->nullable()->default(0);
			$table->string('status')->nullable();
			$table->text('message')->nullable();
			$table->dateTime('object_last_updated')->nullable();
			$table->string('qb_object_id')->nullable();
			$table->integer('jp_object_id')->nullable();
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
			$table->dropColumn('action');
			$table->dropColumn('object');
			$table->dropColumn('object_id');
			$table->dropColumn('parent_id');
			$table->dropColumn('origin');
			$table->dropColumn('status');
			$table->dropColumn('message');
			$table->dropColumn('object_last_updated');
			$table->dropColumn('qb_object_id');
			$table->dropColumn('jp_object_id');
		});
	}

}
