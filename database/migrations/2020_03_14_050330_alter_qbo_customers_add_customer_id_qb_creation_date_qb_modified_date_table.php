<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterQboCustomersAddCustomerIdQbCreationDateQbModifiedDateTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->integer('company_id')->index()->after('id');
			$table->dateTime('qb_creation_date')->after('created_at');
			$table->dateTime('qb_modified_date')->after('updated_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('qbo_customers', function(Blueprint $table)
		{
			$table->dropColumn('company_id');
			$table->dropColumn('qb_creation_date');
			$table->dropColumn('qb_modified_date');
		});
	}

}
