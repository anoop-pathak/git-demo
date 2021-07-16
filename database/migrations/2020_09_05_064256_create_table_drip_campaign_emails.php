<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDripCampaignEmails extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drip_campaign_emails', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('drip_campaign_id')->index();
			$table->integer('company_id')->index();
			$table->integer('email_template_id')->nullable();
			$table->string('subject')->index()->nullable();
			$table->text('content')->nullable();
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
		Schema::drop('drip_campaign_emails');
	}

}
