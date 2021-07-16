<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AlterJobPaymentsAddIndexRefIdIndex extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_payments', function(Blueprint $table) {
 			if (!isIndexExists('job_payments', 'job_payments_ref_to_index')) {
 				$table->index('ref_to');
 			}
 			if (!isIndexExists('job_payments', 'job_payments_ref_id_index')) {
 				$table->index('ref_id');
 			}
 			if (!isIndexExists('job_payments', 'job_payments_quickbook_id_index')) {
 				$table->index('quickbook_id');
 			}
 			if (!isIndexExists('job_payments', 'job_payments_created_by_index')) {
 				$table->index('created_by');
 			}
 			if (!isIndexExists('job_payments', 'job_payments_canceled_index')) {
 				$table->index('canceled');
 			}
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('job_payments', function(Blueprint $table)
 		{
			//
 		});
 	}
 }