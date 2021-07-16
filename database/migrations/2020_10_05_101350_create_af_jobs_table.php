<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_jobs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->string('af_id')->nullable()->comment('id');
			$table->string('owner_id')->index()->nullable()->comment('OwnerId');
			$table->string('name')->nullable()->comment('Name');
			$table->string('job_number')->nullable()->comment('Job_Number__c');
			$table->text('comments')->nullable()->comment('i360__Comments__c');
			$table->string('job_type')->nullable()->comment('i360__Job_Type__c');
			$table->string('project_id')->index()->nullable()->comment('i360__Project_ID_Text__c');
			$table->string('project_manager_id')->nullable()->comment('i360__Project_Manager__c');
			$table->string('project_number')->nullable()->comment('i360__Project_Number__c');
			$table->string('af_customer_af_id')->index()->nullable()->comment('i360__Prospect__c');
			$table->string('status')->index()->nullable()->comment('i360__Status__c');
			$table->double('total_cost', 10, 2)->nullable()->comment('i360__Project_Costs_Total__c');
			$table->double('receipts_adjustment_total', 10, 2)->nullable()->comment('supportworks__Receipts_Adjustment_Total__c');
			$table->double('total_sale', 10, 2)->nullable()->comment('supportworks__Total_Sale_Items__c');
			$table->double('summary_total_with_tax', 10, 2)->nullable()->comment('supportworks__Summary_Total_With_Tax__c');
			$table->double('total_with_tax', 10, 2)->nullable()->comment('supportworks__Total_With_Tax__c');
			$table->text('options')->nullable()->comment('store all data in json format.');
			$table->string('created_by')->index()->nullable()->comment('CreatedById');
			$table->string('updated_by')->index()->nullable()->comment('LastModifiedById');
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
		Schema::drop('af_jobs');
	}

}
