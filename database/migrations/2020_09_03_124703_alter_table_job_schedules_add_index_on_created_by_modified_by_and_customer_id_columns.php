<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobSchedulesAddIndexOnCreatedByModifiedByAndCustomerIdColumns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_schedules', function(Blueprint $table) {
			$table->index('created_by');
			$table->index('modified_by');
			$table->index('customer_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_schedules', function(Blueprint $table) {
			$table->dropIndex(['customer_id']);
			$table->dropIndex(['modified_by']);
			$table->dropIndex(['created_by']);
		});
	}

}
