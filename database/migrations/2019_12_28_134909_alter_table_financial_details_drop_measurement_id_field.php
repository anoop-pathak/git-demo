<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFinancialDetailsDropMeasurementIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		if(Schema::hasColumn('financial_details', 'measurement_id')) {
			Schema::table('financial_details', function(Blueprint $table)
			{
				$table->dropColumn('measurement_id');
			});			
		}

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		
	}

}
