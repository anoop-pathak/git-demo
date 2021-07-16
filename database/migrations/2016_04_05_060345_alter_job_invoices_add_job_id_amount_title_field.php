<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInvoicesAddJobIdAmountTitleField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_invoices', function($table)
		{
			$table->integer('customer_id')->after('id');
			$table->integer('job_id')->after('customer_id');
			$table->string('title')->after('job_id');
			$table->float('amount')->after('title');
			$table->text('detail')->after('amount');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_invoices', function($table)
		{
			$table->dropColumn('customer_id');
			$table->dropColumn('job_id');
			$table->dropColumn('title');
			$table->dropColumn('amount');
			$table->dropColumn('detail');
		});
	}

}
