<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOauthAccessTokensAddPassportAuthColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('oauth_access_tokens', function(Blueprint $table) {
            $table->string('id',100)->change();
            $table->integer('user_id')->nullable();
            $table->integer('client_id')->nullable();
            $table->string('name')->nullable();
            $table->text('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at')->nullable();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('oauth_access_tokens', function(Blueprint $table) {
            $table->string('id',40)->change();
            $table->dropColumn('user_id');
            $table->dropColumn('client_id');
            $table->dropColumn('name');
            $table->dropColumn('scopes');
            $table->dropColumn('revoked');
            $table->dropColumn('expires_at');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
