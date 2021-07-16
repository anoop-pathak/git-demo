<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketSourceSpentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('market_source_spents', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id');
			$table->integer('referral_id');
			$table->float('amount')->nullable();
			$table->text('description')->nullable();
			$table->date('date');
			$table->integer('created_by');
			$table->integer('deleted_by')->nullable();
			$table->softDeletes();
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
		Schema::drop('market_source_spents');
	}

}

