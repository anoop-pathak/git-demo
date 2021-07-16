<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionPlansAddSetupFeeFieldModifiyAmountField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE subscription_plans MODIFY COLUMN amount FLOAT(8,2)');
		Schema::table('subscription_plans', function($table){
			$table->float('setup_fee')->after('amount')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE subscription_plans MODIFY COLUMN amount VARCHAR(255)');
		Schema::table('subscription_plans', function($table){
			$table->dropColumn('setup_fee');
		});
	}

}
