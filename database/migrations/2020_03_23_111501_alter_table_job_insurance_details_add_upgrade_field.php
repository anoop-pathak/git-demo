<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableJobInsuranceDetailsAddUpgradeField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_insurance_details', function(Blueprint $table) {
			$table->string('upgrade')->nullable()->after('rcv');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_insurance_details', function(Blueprint $table) {
			$table->dropColumn('upgrade');
		});
	}

}
