<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterJobsMarkArchivedJobsDeletedIfCustomerDeleted extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		DB::statement('UPDATE jobs
 			INNER JOIN customers ON customers.id = jobs.customer_id
 			SET jobs.deleted_at = customers.deleted_at,
 			jobs.deleted_by = customers.deleted_by
 			WHERE jobs.archived IS NOT NULL
 			AND customers.deleted_at IS NOT NULL
 			AND jobs.parent_id IS NULL
 			AND jobs.deleted_at IS NULL;');
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