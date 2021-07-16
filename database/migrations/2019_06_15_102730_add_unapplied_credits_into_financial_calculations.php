<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnappliedCreditsIntoFinancialCalculations extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_financial_calculations', function(Blueprint $table) {
 			$table->decimal('unapplied_credits')->after('total_credits')->nullable();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_financial_calculations', function(Blueprint $table) {
			$table->dropColumn('unapplied_credits');
		});
	}

}
