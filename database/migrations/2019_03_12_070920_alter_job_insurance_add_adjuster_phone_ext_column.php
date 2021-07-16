<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobInsuranceAddAdjusterPhoneExtColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_insurance_details', function(Blueprint $table) {
			$table->string('adjuster_phone_ext')->nullable()->after('phone');
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
			$table->dropColumn('adjuster_phone_ext');
		});
	}

}
