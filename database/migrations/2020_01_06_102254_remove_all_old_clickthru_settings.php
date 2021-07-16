<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveAllOldClickthruSettings extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		DB::table('estimate_type_layers')->truncate();
		DB::table('waterproofing')->truncate();
		DB::table('estimate_levels')->truncate();
		DB::table('estimate_chimnies')->truncate();
		DB::table('estimate_ventilations')->truncate();
		DB::table('access_to_home')->truncate();
		DB::table('estimate_structures')->truncate();
		DB::table('estimate_gutters')->truncate();
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
	}

}
