<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterWorkflowStagesAddSaleAutomationFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('workflow_stages', function($table){
			$table->boolean('send_customer_email')->default(false);
			$table->boolean('send_push_notification')->default(false);
			$table->boolean('create_tasks')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('workflow_stages', function($table){
			$table->dropColumn('send_customer_email');
			$table->dropColumn('send_push_notification');
			$table->dropColumn('create_tasks');
		});
	}

}
