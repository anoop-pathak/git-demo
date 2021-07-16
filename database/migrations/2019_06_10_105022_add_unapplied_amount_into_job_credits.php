<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class AddUnappliedAmountIntoJobCredits extends Migration {
 	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
 	public function up()
 	{
 		Schema::table('job_credits', function(Blueprint $table) {
 			$table->decimal('unapplied_amount')->after('amount')->nullable();
 			$table->string('status')->after('method')->nullable();
 		});
 	}
 	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
 	public function down()
 	{
 		Schema::table('job_credits', function(Blueprint $table) {
 			$table->dropColumn('unapplied_amount');
 			$table->dropColumn('status');
 		});
 	}
 }