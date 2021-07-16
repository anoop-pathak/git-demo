<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShareOnHopAtFieldToEstimationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->string('share_on_hop_at')->nullable();
		});
		
		DB::statement('UPDATE estimations SET share_on_hop_at = updated_at WHERE share_on_hop = 1');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('estimations', function(Blueprint $table)
		{
			$table->dropColumn('share_on_hop_at');
		});
	}

}
