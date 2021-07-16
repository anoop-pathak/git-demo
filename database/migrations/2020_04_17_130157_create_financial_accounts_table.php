<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFinancialAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('financial_accounts', function(Blueprint $table) 
		{
			$table->engine = 'InnoDB';
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('name');
			$table->integer('parent_id')->nullable();
			$table->string('account_type');
			$table->string('account_sub_type');
			$table->string('classification')->nullable();
			$table->integer('created_by');
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
		Schema::drop('financial_accounts');
	}

}
