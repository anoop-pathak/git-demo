<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPhonesAddIndexOnCustomerId extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('phones', function($table) 
		{
			if (!isIndexExists('phones', 'phones_customer_id_index')) {
				
				$table->index('customer_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('phones', function($table) 
		{
			$table->dropindex('phones_customer_id_index');
		});
	}

}
