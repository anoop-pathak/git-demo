<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Console\Command;

class AttachWorksheetTemplates extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:attach_worksheet_templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move worksheet templates from settings to worksheet templates table';

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
        $settings = Setting::where('key', 'PROPOSAL_WORKSHEET_TEMPLATE')->get();

        foreach ($settings as $key => $setting) {
            $company = Company::find($setting->company_id);

            if ($company && ine($setting->value, 'template_id')) {
                $company->templates()->sync((array)$setting->value['template_id']);
            }
        }
    }
}
