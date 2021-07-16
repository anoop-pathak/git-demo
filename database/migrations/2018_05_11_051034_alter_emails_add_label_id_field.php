<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailsAddLabelIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('emails', function(Blueprint $table)
		{
			$table->integer('label_id')->unsigned()->nullable();
			$table->foreign('label_id')->references('id')->on('email_labels');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('emails', function(Blueprint $table)
		{
			$table->dropForeign('label_id');
			$table->dropColumn('label_id');
		});
	}

}
