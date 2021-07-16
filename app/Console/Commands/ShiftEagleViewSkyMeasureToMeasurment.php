<?php

namespace App\Console\Commands;

use App\Models\Estimation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftEagleViewSkyMeasureToMeasurment extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:shift_eagle_view_sky_measure_to_measurement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shift eagleview & sky measurure to measurement.';

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
        $this->shiftEstimates();
    }

    public function shiftEstimates()
    {

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $estimations = Estimation::where(function ($query) {
            $query->whereNotNull('sm_order_id')
                ->orwhereNotNull('ev_report_id');
        })->withTrashed()
        ->chunk(200, function ($estimations) {
            $data = [];
            $estimateIds = [];
            foreach ($estimations as $estimate) {
                $estimateIds[] = $estimate->id;
                $data[] = [
                    'company_id' => $estimate->company_id,
                    'job_id' => $estimate->job_id,
                    'title' => $estimate->title,
                    'is_file'    => $estimate->is_file,
                    'file_name'  => $estimate->file_name,
                    'file_size'  => $estimate->file_size,
                    'file_mime_type' => $estimate->file_mime_type,
                    'ev_report_id' => $estimate->ev_report_id,
                    'sm_order_id' => $estimate->sm_order_id,
                    'ev_file_type_id' => $estimate->ev_file_type_id,
                    'created_by' => $estimate->created_by,
                    'created_at' => $estimate->created_at,
                    'updated_at' => $estimate->updated_at,
                    'deleted_by' => $estimate->deleted_by ? $estimate->deleted_by : null,
                    'deleted_at' => $estimate->deleted_at,
                ];
            }
            DB::table('measurements')->insert($data);
            DB::table('estimations')->whereIn('id', $estimateIds)
                ->whereNull('deleted_at')
                ->update([
                    'deleted_by' => 1,
                    'deleted_at' => \Carbon\Carbon::now()
                ]);
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
