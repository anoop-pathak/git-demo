<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInsuranceDetailsAddAcvAndTotalField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_insurance_details', function($table){
			$table->string('acv')->nullable()->after('rcv');
			$table->string('total')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_insurance_details', function($table){
			$table->dropColumn('acv');
			$table->dropColumn('total');
		});
	}

}
