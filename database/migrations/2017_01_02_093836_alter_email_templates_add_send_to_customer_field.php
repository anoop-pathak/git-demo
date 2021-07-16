<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterEmailTemplatesAddSendToCustomerField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('email_templates', function(Blueprint $table)
		{
			$table->boolean('send_to_customer')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('email_templates', function(Blueprint $table)
		{
			$table->dropColumn('send_to_customer');
		});
	}

}
