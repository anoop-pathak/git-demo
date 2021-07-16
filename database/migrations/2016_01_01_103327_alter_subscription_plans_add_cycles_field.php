<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterSubscriptionPlansAddCyclesField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('subscription_plans', function(Blueprint $table){
			$table->string('cycles')->default('unlimited')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('subscription_plans', function(Blueprint $table){
			$table->dropColumn('cycles');
		});
	}

}
