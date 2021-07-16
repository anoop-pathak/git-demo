<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReferralIdFieldToAfReferralsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_referrals', function(Blueprint $table)
		{
			$table->integer('referral_id')->nullable()->after('group_id');
			$table->integer('af_id')->nullable()->after('referral_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('af_referrals', function(Blueprint $table)
		{
			$table->dropColumn(['referral_id', 'af_id']);
		});
	}

}
