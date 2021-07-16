<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfLeadSourcesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_lead_sources', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('af_id')->nullable();
			$table->string('owner_id')->nullable();
			$table->string('name')->index()->comment("name");
			$table->text('comments')->nullable()->comment("i360__Comments__c");
			$table->text('components')->nullable()->comment("i360__Components__c");
			$table->string('prospect_id')->nullable()->comment("i360__Prospect_Id__c");
			$table->string('prospect')->nullable()->comment("i360__Prospect__c");
			$table->string('marketing_source_id')->nullable()->comment("i360__Source__c");
			$table->string('prospect_email')->nullable()->comment("supportworks__Prospect_Email_WS__c");
			$table->text('options')->nullable()->comment('all other fields in json.');
			$table->string('created_by')->nullable()->comment('CreatedById');
			$table->string('updated_by')->nullable()->comment('LastModifiedById');
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
		Schema::drop('af_lead_sources');
	}

}
