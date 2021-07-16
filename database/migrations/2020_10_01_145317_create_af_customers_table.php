<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAfCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('af_customers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('group_id')->index()->comment("standard user group id => 3");
			$table->integer('customer_id')->nullable()->index()->comment("JobProgress customers table ref id.");
			$table->string('af_id')->nullable()->comment('id');
			$table->string('rep_id')->nullable()->comment('OwnerId');
			$table->string('referred_by_type')->nullable()->comment('JobProgress');
			$table->string('company_name')->nullable()->comment('supportworks__Company__c');
			$table->string('first_name')->nullable()->comment('i360__Primary_First_Name__c');
			$table->string('last_name')->nullable()->comment('i360__Primary_Last_Name__c');
			$table->string('email')->nullable()->comment('i360__Primary_Email__c');
			$table->string('secondary_first_name')->nullable()->comment('i360__Secondary_First_Name__c');
			$table->string('secondary_last_name')->nullable()->comment('i360__Secondary_Last_Name__c');
			$table->string('billing_address')->nullable()->comment('supportworks__Billing_Address__c');
			$table->string('billing_city')->nullable()->comment('supportworks__Billing_City__c');
			$table->string('billing_state')->nullable()->comment('supportworks__Billing_State__c');
			$table->string('billing_zip')->nullable()->comment('supportworks__Billing_Zip__c');
			$table->string('customer_address')->nullable()->comment('supportworks__Mailing_Address__c');
			$table->string('customer_city')->nullable()->comment('supportworks__Mailing_City__c');
			$table->string('customer_state')->nullable()->comment('supportworks__Mailing_State__c');
			$table->string('customer_zip')->nullable()->comment('supportworks__Mailing_Zip_Postal_Code__c');
			$table->string('management_company')->nullable();
			$table->string('property_name')->nullable();
			$table->string('origin')->nullable()->comment('JobProgress');
			$table->string('note')->nullable()->comment('i360__Comments__c, i360__Home_Value__c, i360__Not_Qualified_Reason__c, i360__Restriction_Comments__c, i360__Year_Home_Built__c, i360__Year_Home_Purchased__c, i361__DNC_Waiver_Description_1__c, i361__DNC_Waiver_Description_2__c, i361__DNC_Waiver_Description_3__c, supportworks__Company__c, supportworks__Next_Annual_Maintenance_Date__c, supportworks__Service_Appointment_Date__c, supportworks__Annual_Maintenance_Content__c, supportworks__Most_Recent_Product_Category__c,');
			$table->text('options')->nullable()->comment('store all data in json format.');
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
		Schema::drop('af_customers');
	}

}
