<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDripCampaigns extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drip_campaigns', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('customer_id')->index()->nullable();
			$table->integer('job_id')->index()->nullable();
			$table->string('name')->index()->nullable();
			$table->string('status')->index()->nullable();
			$table->string('repeat')->index()->nullable();
			$table->string('occurence')->nullable();
			$table->integer('interval')->nullable();
			$table->timestamp('until_date')->nullable();
			$table->string('by_day')->nullable();
			$table->string('job_current_stage_code')->index()->nullable();
			$table->string('job_end_stage_code')->nullable();
			$table->integer('created_by');
			$table->integer('canceled_by')->nullable();
			$table->string('canceled_note')->nullable();
			$table->dateTime('canceled_date_time')->nullable();
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
		Schema::drop('drip_campaigns');
	}
}
