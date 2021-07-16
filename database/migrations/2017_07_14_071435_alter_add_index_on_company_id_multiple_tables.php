<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddIndexOnCompanyIdMultipleTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('appointments', function($table){
			if(!isIndexExists('appointments', 'appointments_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('customers', function($table){
			if(!isIndexExists('customers', 'customers_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('emails', function($table){
			if(!isIndexExists('emails', 'emails_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('job_schedules', function($table){
			if(!isIndexExists('job_schedules', 'job_schedules_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('jobs', function($table){
			if(!isIndexExists('jobs', 'jobs_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('messages', function($table){
			if(!isIndexExists('messages', 'messages_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('referrals', function($table){
			if(!isIndexExists('referrals', 'referrals_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('settings', function($table){
			if(!isIndexExists('settings', 'settings_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('tasks', function($table){
			if(!isIndexExists('tasks', 'tasks_company_id_index')){
				$table->index('company_id');
			}
		});

		Schema::table('users', function($table){
			if(!isIndexExists('users', 'users_company_id_index')){
				$table->index('company_id');
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
		Schema::table('appointments', function($table){
			
			$table->dropIndex('appointments_company_id_index');
		});

		Schema::table('customers', function($table){
			
			$table->dropIndex('customers_company_id_index');
		});

		Schema::table('emails', function($table){
			
			$table->dropIndex('emails_company_id_index');
		});

		Schema::table('job_schedules', function($table){
			
			$table->dropIndex('job_schedules_company_id_index');
		});

		Schema::table('jobs', function($table){
			
			$table->dropIndex('jobs_company_id_index');
		});

		Schema::table('messages', function($table){
			
			$table->dropIndex('messages_company_id_index');
		});

		Schema::table('referrals', function($table){
			
			$table->dropIndex('referrals_company_id_index');
		});

		Schema::table('settings', function($table){
			
			$table->dropIndex('settings_company_id_index');
		});

		Schema::table('tasks', function($table){
			
			$table->dropIndex('tasks_company_id_index');
		});

		Schema::table('users', function($table){
			
			$table->dropIndex('users_company_id_index');
		});
	}

}
