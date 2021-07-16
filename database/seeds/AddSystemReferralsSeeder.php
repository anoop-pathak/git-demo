<?php
use Illuminate\Database\Seeder;
use App\Models\Referral;

class AddSystemReferralsSeeder extends Seeder
{
    public function run()
    {
        Referral::where('company_id', 0)->forceDelete();
        $referrals = [
            [
                'name' => 'Zapier',
            ],
        ];

        foreach ($referrals as $key => $referral) {
            $referral = Referral::firstOrCreate([
                'name' => $referral['name'],
                'company_id' => 0,
            ]);
        }
    }
}
