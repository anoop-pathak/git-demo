<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddDueCommissionToTheJobCommissions extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_commissions', function(Blueprint $table)
 		{
 			$table->decimal('due_amount')->after('amount')->nullable();
 			$table->string('status')->nullable();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('job_commissions', function(Blueprint $table)
 		{
 			$table->dropColumn('due_amount');
 			$table->dropColumn('status');
 		});
 	}
 }
