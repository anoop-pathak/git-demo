<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionsAddRunningPlanCodeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('subscriptions', function($table){
			$table->string('current_plan_code')->after('next_renewal_plan')->comment('It will update only on renewal if plan changed. Its needed because subscription_plan_id changing one cycle before renewal.');
		});

		DB::statement('UPDATE subscriptions INNER JOIN subscription_plans ON subscription_plans.id = subscriptions.subscription_plan_id SET current_plan_code=subscription_plans.code');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('subscriptions', function($table){
			$table->dropColumn('current_plan_code');
		});
	}

}
