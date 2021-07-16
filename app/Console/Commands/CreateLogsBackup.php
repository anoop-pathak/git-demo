<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateLogsBackup extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create-logs-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Logs file backup';

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
        set_time_limit(0);

        EnterPassword :
        $password = $this->secret('What is the password?');
        if ($password != config('jp.developer_secret')) {
            $this->error('Incorrect Password.');
            goto EnterPassword;
        }
        $logsPath = storage_path() . '/logs';
        $filePath = $logsPath . '/laravel.log';
        $backupFileName = 'laravel' . date('Ymdhis') . '.log';
        rename($filePath, $logsPath . '/' . $backupFileName);
        fopen($filePath, 'w');
        chmod($filePath, 0755);
        $this->info('Backup Created : ' . $backupFileName);
    }
}
