<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeAfIdFieldInAfReferralsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('af_referrals', function(Blueprint $table)
		{
			$table->dropColumn(['af_id']);
		});

		Schema::table('af_referrals', function(Blueprint $table)
		{
			$table->string('af_id')->nullable()->after('referral_id');
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
			$table->dropColumn(['af_id']);
		});

		Schema::table('af_referrals', function(Blueprint $table)
		{
			$table->integer('af_id')->nullable()->after('referral_id');
		});

	}

}
