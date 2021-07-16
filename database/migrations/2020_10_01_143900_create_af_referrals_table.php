<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfReferralsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_referrals', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('user_id')->nullable();
			$table->string('name')->index()->comment("name");
			$table->text('options')->nullable()->comment('all other fields in json.');
			$table->string('csv_filename')->nullable()->comment('name of file from which data is imported.');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('af_referrals');
	}

}
