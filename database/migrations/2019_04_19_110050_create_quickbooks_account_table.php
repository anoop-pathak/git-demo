<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuickbooksAccountTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quickbooks_account', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->string('name');
			$table->string('list_id');
			$table->boolean('is_active');
			$table->integer('sub_level');
			$table->string('account_type');
			$table->string('account_number');
			$table->text('description');
			$table->string('balance');
			$table->string('total_balance');
			$table->string('tax_line_id')->nullable();
			$table->string('tax_line_name')->nullable();
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
		Schema::drop('quickbooks_account');
	}

}
