<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddMaterialListThumb extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_material_list_thumb';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add material list thumb from worksheet';

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
        DB::statement("UPDATE material_lists ml
			LEFT OUTER JOIN worksheets ws
				ON ws.id = ml.worksheet_id
			SET ml.thumb = ws.thumb");
    }
}
