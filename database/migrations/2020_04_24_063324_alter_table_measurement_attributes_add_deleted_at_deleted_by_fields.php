<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMeasurementAttributesAddDeletedAtDeletedByFields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('measurement_attributes', function(Blueprint $table) {
   			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
   		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('measurement_attributes', function(Blueprint $table) {
			$table->dropColumn('deleted_by');
			$table->dropColumn('deleted_at');
		});
	}

}
