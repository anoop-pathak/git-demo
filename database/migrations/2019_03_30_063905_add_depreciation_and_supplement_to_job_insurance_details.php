<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDepreciationAndSupplementToJobInsuranceDetails extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_insurance_details', function(Blueprint $table) {
			$table->string('depreciation')->nullable()->after('acv');
			$table->string('supplement')->nullable()->after('rcv');
			$table->string('net_claim')->nullable()->after('supplement');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_insurance_details', function($table) {
			$table->dropColumn('depreciation');
			$table->dropColumn('supplement');
			$table->dropColumn('net_claim');
	});
	}

}
