<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class UpdateDeletedCustomersJobs extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		DB::statement("
 			UPDATE jobs ,
 			customers
 			SET jobs.deleted_at = customers.deleted_at ,
 			jobs.deleted_by = customers.deleted_by
 			WHERE jobs.customer_id = customers.id
 			AND customers.deleted_at IS NOT NULL
 			");
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
		//
 	}
 }