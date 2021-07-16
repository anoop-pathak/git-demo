<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCompanyContactsAddContactIdColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('company_contacts', function(Blueprint $table) {
			$table->integer('contact_id')->after('company_id')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('company_contacts', function(Blueprint $table) {
			$table->dropColumn('contact_id');
		});
	}

}
