<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAccountManagerAddAddressCityStateIdCountryIdZipAddressLine1Fields extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('account_managers', function($table){
			$table->string('address');
			$table->string('address_line_1')->nullable();
			$table->string('city');
			$table->string('country_id');
			$table->string('zip');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('account_managers', function($table){
			$table->dropColumn('address');
			$table->dropColumn('address_line_1')->nullable();
			$table->dropColumn('city');
			$table->dropColumn('country_id');
			$table->dropColumn('zip');
		});
	}

}
