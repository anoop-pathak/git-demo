<?php
use Illuminate\Database\Seeder;

class OauthClientsTableSeeder extends Seeder
{

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('oauth_clients')->truncate();

        $clients = [
            [
                'id' => '12345',
                'secret' => 'XraqRySfIhUTuvdfz7ATuJxXYf8aX5MY',
                'name' => 'jp_web_app_plus',
                'password_client' => true,
                'personal_access_client' => false,
            ],
            [
                'id' => '123456',
                'secret' => 'dcZ4aEKMpLaS1aYhTIcdRQrlqSjb5dAm',
                'name' => 'jp_mobile_app_plus',
                'password_client' => true,
                'personal_access_client' => false,
            ],
            [
                'id'     => '42766958',
                'secret' => 'schs1EKRpLaS1auhTIc25JrlWSjkry1P',
                'name'   => 'jp_plugin',
                'password_client' => true,
                'personal_access_client' => false,
            ],
            [
                'id'     => '1234567',
                'secret' => 'IiuwDDuKD6bPd5nlAIMhH9VHpEgRmoCprBPDk6sB',
                'name'   => 'Open API',
                'password_client' => false,
                'personal_access_client' => true,
            ],
            [
                'id' => '1234214',
                'secret' => 'DOjh1IclPUSWFUFscWpIVgQlfSAEwFnceTLMBXmQ',
                'name' => 'spotio_client',
                'password_client' => true,
                'personal_access_client' => false,
            ]
        ];

        DB::table('oauth_clients')->insert($clients);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
