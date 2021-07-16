<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class ActivateABCSupplierForSubscribers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:activate_abc_supplier';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actibate ABC supplier for subscribers.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $subscriberId = $this->ask('Enter Subscriber Id :');
        $company = Company::find($subscriberId);
        if (!$company) {
            $this->error('Invalid Subscriber Id.');
            return;
        }
        $password = $this->secret('What is the password?');
        if ($password != config('jp.developer_secret')) {
            $this->error('Incorrect Password.');
            return;
        }
        $company->suppliers()->sync([1]);

        $this->info('Done.');
    }
}
