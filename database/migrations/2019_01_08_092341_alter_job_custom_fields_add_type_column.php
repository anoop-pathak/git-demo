<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterJobCustomFieldsAddTypeColumn extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_custom_fields', function(Blueprint $table) {
			$table->string('type')->default('string');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_custom_fields', function(Blueprint $table) {
			$table->string('type')->default('string');
		});
	}

}
