<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterOnboardChecklistSectionsAddPositionField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('onboard_checklist_sections', function(Blueprint $table)
		{
			$table->integer('position')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('onboard_checklist_sections', function(Blueprint $table)
		{
			$table->dropColumn('position');
		});
	}

}
