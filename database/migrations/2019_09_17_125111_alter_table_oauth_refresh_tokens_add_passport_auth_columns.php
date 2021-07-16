<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOauthRefreshTokensAddPassportAuthColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('oauth_refresh_tokens', function(Blueprint $table) {
            $table->string('id', 100)->change();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('oauth_refresh_tokens', function(Blueprint $table) {
            $table->string('id', 40)->change();
            $table->dropColumn('revoked');
            $table->dropColumn('expires_at');
        });
    }
}
