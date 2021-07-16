<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfAppointmentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_appointments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->integer('appointment_id')->nullable()->comment('JobProgress appointment id.');
			$table->string('af_id')->nullable()->comment('id');
			$table->string('af_owner_id')->nullable()->comment('OwnerId');
			$table->string('lead_source_id')->nullable()->comment('i360__Lead_Source_Id__c');
			$table->string('prospect_id')->nullable()->comment('i360__Prospect_Id__c');
			$table->string('name')->index()->nullable()->comment("Name");
			$table->string('email')->nullable()->comment("i360__Email_Address__c");
			$table->decimal('latitude', 10, 8)->nullable()->comment('i360__Latitude__c');
			$table->decimal('longitude', 11, 8)->nullable()->comment('i360__Longitude__c');
			$table->text('address')->nullable()->comment('i360__Address__c');
			$table->string('city')->nullable()->comment('i360__City__c');
			$table->string('county')->nullable()->comment('i360__County__c');
			$table->string('state')->nullable()->comment('i360__State__c');
			$table->string('zip')->nullable()->comment('i360__Zip__c');
			$table->string('start_time')->nullable()->comment('i360__Start_Time__c');
			$table->datetime('start_datetime')->nullable()->comment('i360__Start__c');
			$table->datetime('end_datetime')->nullable()->comment('i360__End__c');
			$table->string('status')->nullable()->comment('i360__Status__c');
			$table->string('type')->nullable()->comment('i360__Type__c');
			$table->integer('year_home_buillt')->nullable()->comment('i360__Year_Home_Built__c');
			$table->text('comments')->nullable()->comment('i360__Comments__c');
			$table->text('components')->nullable()->comment('i360__Components_1__c');
			$table->text('interests_summary')->nullable()->comment('i360__Interests_Summary__c');
			$table->text('calendar_custom_text')->nullable()->comment('i360__Calendar_Custom_Text__c');
			$table->string('appointment_duration')->nullable()->comment('i360__Duration__c');
			$table->double('price_1', 10, 2)->nullable()->comment('i360__Price_Given_1__c');
			$table->double('price_2', 10, 2)->nullable()->comment('i360__Price_Given_2__c');
			$table->double('price_3', 10, 2)->nullable()->comment('i360__Price_Given_3__c');
			$table->double('quoted_amount', 10, 2)->nullable()->comment('i360__Quoted_Amount__c');
			$table->text('result_1')->nullable()->comment('i360__Result_1__c');
			$table->text('result_detail_1')->nullable()->comment('i360__Result_Detail_1__c');
			$table->text('result')->nullable()->comment('i360__Result__c');
			$table->integer('revision_number')->nullable()->comment('i360__Revision_Number__c');
			$table->string('sales_rep_1_next_appointment')->nullable()->comment('i360__Sales_Rep_1_Next_Appointment__c');
			$table->string('sales_rep_1')->nullable()->comment('i360__Sales_Rep_1__c');
			$table->string('sales_rep_2_next_appointment')->nullable()->comment('i360__Sales_Rep_2_Next_Appointment__c');
			$table->string('sales_rep_2')->nullable()->comment('i360__Sales_Rep_2__c');
			$table->string('source_type')->nullable()->comment('i360__Source_Type__c');
			$table->string('source_id')->nullable()->comment('i360_source_c');
			$table->string('talked_to')->nullable()->comment('i360__Talked_To__c');
			$table->string('confirmed_by')->nullable()->comment('i360__Confirmed_By__c');
			$table->datetime('confirmed_at')->nullable()->comment('i360__Confirmed_On__c');
			$table->string('appointment_set_by')->nullable()->comment('i360__Appt_Set_By__c');
			$table->datetime('appointment_set_at')->nullable()->comment('i360__Appt_Set_On__c');
			$table->string('product_category_1_type')->nullable()->comment('supportworks__Product_Category_1_Type__c');
			$table->string('product_category_1')->nullable()->comment('supportworks__Product_Category_1__c');
			$table->string('issued_by')->nullable()->comment('i360__Issued_By__c');
			$table->datetime('issued_at')->nullable()->comment('i360__Issued_On__c');
			$table->string('canceled_by')->nullable()->comment('supportworks__Canceled_By__c');
			$table->datetime('canceled_on')->nullable()->comment('supportworks__Canceled_On__c');
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
		Schema::drop('af_appointments');
	}

}
