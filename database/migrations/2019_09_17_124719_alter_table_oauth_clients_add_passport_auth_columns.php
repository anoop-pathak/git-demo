<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOauthClientsAddPassportAuthColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->integer('user_id')->nullable();
            $table->string('secret', 100)->change();
            $table->text('redirect')->nullable();
            $table->boolean('personal_access_client')->default(false);
            $table->boolean('password_client')->default(true);
            $table->boolean('revoked')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_clients', function(Blueprint $table) {
            $table->dropColumn('user_id');
            $table->string('secret', 40)->change();
            $table->dropColumn('redirect');
            $table->dropColumn('personal_access_client');
            $table->dropColumn('password_client');
            $table->dropColumn('revoked');
        });
    }
}
