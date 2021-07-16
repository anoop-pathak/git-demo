<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEmailReceipentAddDeliveryBouncedDateTimeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('email_recipient', function(Blueprint $table) {
			$table->timestamp('delivery_date_time')->nullable();
			$table->timestamp('bounce_date_time')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('email_recipient',function(Blueprint $table){
			$table->dropColumn('delivery_date_time');
			$table->dropColumn('bounce_date_time');
		});
	}

}
