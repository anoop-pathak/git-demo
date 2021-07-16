<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddPositionIntoCompanyLicenseNumbers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_license_numbers', function(Blueprint $table)
		{
			$table->integer('position')->after('company_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('company_license_numbers', function(Blueprint $table)
		{
			$table->dropColumn('position');			
		});
	}

}
