<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinancialAccountTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('financial_account_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('classification');
			$table->string('account_type');
			$table->string('account_type_display_name');
			$table->string('account_sub_type');
			$table->string('account_sub_type_display_name');
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
		Schema::drop('financial_account_types');
	}

}
