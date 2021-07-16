<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDripCampaignSchedulers extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drip_campaign_schedulers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('drip_campaign_id')->index();
			$table->timestamp('schedule_date_time');
			$table->string('medium_type')->index();
			$table->string('status')->index();
			$table->string('failed_reason')->nullable();
			$table->dateTime('status_updated_at')->nullable();
			$table->string('outcome_id')->nullable();
			$table->dateTime('canceled_at')->nullable();
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
		Schema::drop('drip_campaign_schedulers');
	}

}
