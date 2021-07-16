<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableClickThruEstimates extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('click_thru_estimates', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->index();
			$table->integer('job_id')->index();
			$table->integer('customer_id')->index();
			$table->integer('manufacturer_id')->index();
			$table->text('level')->nullable();
			$table->text('shingle')->nullable();
			$table->text('underlayment')->nullable();
			$table->text('warranty')->nullable();
			$table->text('type')->nullable();
			$table->decimal('roof_size')->default(0);
			$table->text('structure')->nullable();
			$table->text('complexity')->nullable();
			$table->text('pitch')->nullable();
			$table->text('chimney')->nullable();
			$table->text('others')->nullable();
			$table->decimal('skylight')->default(0);
			$table->text('waterproofing')->nullable();
			$table->text('gutter')->nullable();
			$table->text('access_to_home')->nullable();
			$table->text('notes')->nullable();
			$table->decimal('amount')->default(0);
			$table->decimal('adjustable_amount')->default(0);
			$table->text('adjustable_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('click_thru_estimates');
	}

}
