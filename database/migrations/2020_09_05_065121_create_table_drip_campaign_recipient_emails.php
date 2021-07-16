<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDripCampaignRecipientEmails extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drip_campaign_recipient_emails', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('email_campaign_id')->index();
			$table->integer('company_id')->index();
			$table->string('type')->index();
			$table->string('email')->nullable();
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
		Schema::drop('drip_campaign_recipient_emails');
	}

}
