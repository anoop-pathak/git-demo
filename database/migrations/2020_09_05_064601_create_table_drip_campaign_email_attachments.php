<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableDripCampaignEmailAttachments extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drip_campaign_email_attachments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('drip_campaign_email_id')->index();
			$table->integer('company_id')->index();
			$table->string('ref_type')->index();
			$table->integer('ref_id')->index();
			$table->integer('new_resource_id')->nullable();
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
		Schema::drop('drip_campaign_email_attachments');
	}
}
