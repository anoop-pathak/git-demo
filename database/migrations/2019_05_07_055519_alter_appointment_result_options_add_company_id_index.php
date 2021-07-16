<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAppointmentResultOptionsAddCompanyIdIndex extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointment_result_options', function(Blueprint $table) {
			if (!isIndexExists('appointment_result_options', 'appointment_result_options_company_id_index')) {
				$table->index('company_id');
			}
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('appointment_result_options', function(Blueprint $table) {
			$table->dropIndex('appointment_result_options_company_id_index');
		});
	}

}
