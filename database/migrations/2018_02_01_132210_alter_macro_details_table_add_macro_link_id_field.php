<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterMacroDetailsTableAddMacroLinkIdField extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('macro_details', function(Blueprint $table)
		{
			$table->integer('macro_link_id')->after('macro_id');
		});

		// DB::statement("UPDATE macro_details INNER JOIN financial_macros ON macro_details.macro_id = financial_macros.macro_id SET macro_details.macro_link_id = financial_macros.id");

	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('macro_details', function(Blueprint $table)
		{
			$table->dropColumn('macro_link_id');
		});
	}

}
