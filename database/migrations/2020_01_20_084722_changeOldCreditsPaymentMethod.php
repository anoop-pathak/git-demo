<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeOldCreditsPaymentMethod extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::statement("UPDATE job_payments
			SET method = 'credit' WHERE credit_id is NOT NULL and canceled is NULL;"
		);

		DB::statement("UPDATE job_credits
			SET method = 'credit' WHERE canceled is NULL;"
		);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		DB::statement("UPDATE job_payments
			SET method = 'cash' WHERE credit_id is NOT NULL and canceled is NULL;"
		);

		DB::statement("UPDATE job_credits
			SET method = 'cash' WHERE canceled is NULL;"
		);
	}

}
