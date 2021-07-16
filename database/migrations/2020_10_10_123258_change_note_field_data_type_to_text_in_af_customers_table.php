<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeNoteFieldDataTypeToTextInAfCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$query = "ALTER TABLE `af_customers` 
				CHANGE `note` `note` TEXT 
				DEFAULT NULL 
				COMMENT 'i360__Comments__c, i360__Home_Value__c, i360__Not_Qualified_Reason__c, i360__Restriction_Comments__c, i360__Year_Home_Built__c, i360__Year_Home_Purchased__c, i361__DNC_Waiver_Description_1__c, i361__DNC_Waiver_Description_2__c, i361__DNC_Waiver_Description_3__c, supportworks__Company__c, supportworks__Next_Annual_Maintenance_Date__c, supportworks__Service_Appointment_Date__c, supportworks__Annual_Maintenance_Content__c, supportworks__Most_Recent_Product_Category__c'";
		DB::statement($query);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$query = "ALTER TABLE `af_customers` 
			CHANGE `note` `note` VARCHAR(255) 
			DEFAULT NULL 
			COMMENT 'i360__Comments__c, i360__Home_Value__c, i360__Not_Qualified_Reason__c, i360__Restriction_Comments__c, i360__Year_Home_Built__c, i360__Year_Home_Purchased__c, i361__DNC_Waiver_Description_1__c, i361__DNC_Waiver_Description_2__c, i361__DNC_Waiver_Description_3__c, supportworks__Company__c, supportworks__Next_Annual_Maintenance_Date__c, supportworks__Service_Appointment_Date__c, supportworks__Annual_Maintenance_Content__c, supportworks__Most_Recent_Product_Category__c'";
		DB::statement($query);
	}

}
