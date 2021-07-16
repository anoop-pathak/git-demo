<?php

namespace App\Console\Commands;

use App\Models\CompanyTrade;
use App\Models\JobType;
use App\Models\Trade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddNewTradeType extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:separate_window_and_door_trade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert Windows And Doors trades to two trades.';

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
        // Find Window and Door trades
        $windowAndDoorTrade = Trade::whereName('WINDOWS & DOORS')->first();
        if (!$windowAndDoorTrade) {
            return;
        }

        // Create New Trade..
        $doorTrade = Trade::create([
            'name' => 'DOORS',
        ]);

        // Assign New Trade to Companies..
        $companytrades = CompanyTrade::whereTradeId($windowAndDoorTrade->id)->get();

        $newComTrades = [];
        foreach ($companytrades as $key => $cTrade) {
            $newComTrades[$key] = [
                'company_id' => $cTrade->company_id,
                'trade_id' => $doorTrade->id,
                // 'color' => $cTrade->color,
            ];
        }

        CompanyTrade::insert($newComTrades);

        //Assign New trade Jobs
        DB::table('job_trade')->where('trade_id', '=', $windowAndDoorTrade->id)
            ->chunk(200, function ($jobTrades) use ($doorTrade) {
                $newJobTrades = [];

                foreach ($jobTrades as $key => $value) {
                    $newJobTrades[$key] = [
                        'job_id' => $value->job_id,
                        'trade_id' => $doorTrade->id,
                        'schedule_id' => $value->schedule_id,
                    ];
                }

                DB::table('job_trade')->insert($newJobTrades);
            });

        // Create worktypes for new trade
        JobType::whereTradeId($windowAndDoorTrade->id)
            ->chunk(200, function ($workTypes) use ($doorTrade) {
                $newWorkTypes = [];

                foreach ($workTypes as $key => $value) {
                    $newWorkTypes[$key] = [
                        'trade_id' => $doorTrade->id,
                        'company_id' => $value->company_id,
                        'name' => $value->name,
                        'type' => $value->type,
                        'color' => $value->color,
                    ];
                }

                JobType::insert($newWorkTypes);
            });

        // Add to template trades
        $templateTrades = DB::table('template_trade')->where('trade_id', '=', $windowAndDoorTrade->id)->get();

        $newTemplateTrades = [];

        foreach ($templateTrades as $key => $value) {
            $newTemplateTrades[$key] = [
                'template_id' => $value->template_id,
                'trade_id' => $doorTrade->id,
            ];
        }

        DB::table('template_trade')->insert($newTemplateTrades);

        // Assign to Account manager..
        $accManagerTrades = DB::table('account_manager_trade')->where('trade_id', '=', $windowAndDoorTrade->id)->get();

        $newAccManagerTrades = [];

        foreach ($accManagerTrades as $key => $value) {
            $newAccManagerTrades[$key] = [
                'account_manager_id' => $value->account_manager_id,
                'trade_id' => $doorTrade->id,
            ];
        }

        DB::table('account_manager_trade')->insert($newAccManagerTrades);

        // Assign to Labour..
        $labourTrades = DB::table('labor_trade')->where('trade_id', '=', $windowAndDoorTrade->id)->get();

        $newLabourTrades = [];

        foreach ($labourTrades as $key => $value) {
            $newLabourTrades[$key] = [
                'user_id' => $value->user_id,
                'trade_id' => $doorTrade->id,
            ];
        }

        DB::table('labor_trade')->insert($newLabourTrades);


        // Update name for window and doors to windows..
        $windowAndDoorTrade->update(['name' => 'WINDOWS']);
    }
}
