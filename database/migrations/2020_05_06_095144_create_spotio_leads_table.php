<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSpotioLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spotio_leads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->nullable();
            $table->string('lead_id')->nullable();
            $table->string('assigned_user_name')->nullable();
            $table->string('updated_at_external_system_user_id')->nullable();
            $table->string('assigned_external_system_user_id')->nullable();
            $table->string('address_unit')->nullable();
            $table->string('value')->nullable();
            $table->string('created_at_utc')->nullable();
            $table->string('created_at_local')->nullable();
            $table->string('updated_at_utc')->nullable();
            $table->string('updated_at_local')->nullable();
            $table->string('lat')->nullable();
            $table->string('long')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('house_number')->nullable();
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('stage_name')->nullable();
            $table->string('assigned_user_email')->nullable();
            $table->string('assigned_user_phone')->nullable();
            $table->string('updated_at_username')->nullable();
            $table->string('updated_at_user_email')->nullable();
            $table->string('company')->nullable();
            $table->string('documents')->nullable();
            $table->text('documents_list')->nullable();
            $table->string('last_visit_result')->nullable();
            $table->text('contacts')->nullable();
            $table->text('contact_custom_fields')->nullable();
            $table->text('lead_custom_fields')->nullable();
            $table->text('log_messages')->nullable();
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
        Schema::dropIfExists('spotio_leads');
    }
}
