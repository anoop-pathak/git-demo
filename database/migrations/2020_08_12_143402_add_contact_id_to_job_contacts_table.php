<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContactIdToJobContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_contacts', function(Blueprint $table)
		{
			$table->integer('contact_id')->after('job_id')->nullable();
			$table->boolean('is_primary')->after('contact_id')->default(0);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_contacts', function(Blueprint $table)
		{
			$table->dropColumn(array('contact_id', 'is_primary'));
		});
	}

}
