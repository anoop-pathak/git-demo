<?php
namespace  App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfAppointment;

class MoveAfAppointmentsToAppointmentsCommand extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_appointments_move_to_appointments';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation appointments from af_appointmenst table to company appointments table.';

    private $inc = 0;
    private $resultOptions = [];
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
	public function fire()
	{
        Config::set('notifications.enabled', false);
        setAuthAndScope(config('jp.american_foundation_system_user_id'));
        setScopeId(config('jp.american_foundation_company_id'));

        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': Start Saving result options.');
        $this->saveResultOptions();
        $repo = App::make('App\Repositories\AppointmentResultOptionRepository');
        $builder = $repo->getBuilder();
        $this->resultOptions = $builder->get();
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': End Saving result options.');

        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': Start move af appointments to Jp Appointments.');
        AfAppointment::with([
                'afUser' => function($q) {$q->select(['id', 'af_id', 'user_id']);},
                'afCustomer' => function($q) {$q->select('id', 'af_id', 'customer_id');}
            ])
            ->chunk(1000, function($items){
                foreach ($items as $item) {

                    if($item->appointment_id) {
                        continue;
                    }
                    $customer = $item->afCustomer;

                    if(!$customer || !$customer->customer_id) {
                        continue;
                    }

                    try {
                        $arrData = $this->fieldsMapping($item);

                        $service = App::make('App\Services\AmericanFoundation\Services\AfAppointmentService');
                        $savedAppointment = $service->createJpAppointment($arrData);

                        $item->appointment_id = $savedAppointment->recurrings->first()->id;
                        $item->save();

                        $service = App::make('App\Services\Appointments\AppointmentService');
                        $appointment = $service->getById($item->appointment_id);
                        $this->attachAppointmentResultOptions($appointment, $item);

                        $this->inc++;
                        if($this->inc %100 == 0) {
                            $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing appointments:- " . $this->inc);
                        }
                    } catch (\Exception $e) {
                        Log::error(Carbon::now()->format('Y-m-d H:i:s') . ": Error in American Foundation Move AfAppointment to appointments table");
                        Log::error($e);
                    }
                }
        });
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processed appointments:- " . $this->inc);
    }

    /**
     * set mapping of fields mapping.
     *
     * @param AfAppointment $item
     * @return void
     */
    private function fieldsMapping(AfAppointment $item)
    {
        $strStartDatetime = Carbon::parse($item->start_datetime)->format('Y-m-d');
        $startDatetime = Carbon::parse($strStartDatetime . " " . $item->start_time);

        $user = $item->afUser;

        $endDatetime = $item->end_datetime;
        if(!$endDatetime) {
            $endDatetimeInstance = Carbon::parse($strStartDatetime . " " . $item->start_time);
            $duration = explode(' ', $item->appointment_duration);
            $duration = $duration[0] ?: 0;
            $endDatetimeInstance->addHours($duration);
            $endDatetime = $endDatetimeInstance->format('Y-m-d H:i:s');
        }
        $data = array(
            'title'             => $item->name,
            'description'       => $this->description($item),
            'start_date_time'   => $startDatetime->format('Y-m-d H:i:s'),
            'end_date_time'     => $endDatetime,
            'location_type'     => "others" ,
            'location'          => $this->setLocation($item),
            'customer_id'       => $item->afCustomer->customer_id,
            'lat'               => $item->latitude,
            'long'              => $item->longitude,
            'occurence'         => 'never_repeat',
            'full_day'          => 0,
            'user_id'           => $user->user_id,
        );
        return $data;
    }

    /**
     * set description using multiple fields combined.
     *
     * @param AfAppointment $item
     * @return void
     */
    private function description($item)
    {
        $des = "";
        if($item->comments) {
            $des .= "Comments: " . $item->comments . "\n";
        }
        if($item->components) {
            $des .= "Components: " . $item->components . "\n";
        }

        if($item->interests_summary) {
            $des .= "Interests Summary: " . $item->interests_summary . "\n";
        }

        if($item->calendar_custom_text) {
            $des .= "Calendar Custom Text: " . $item->calendar_custom_text . "\n";
        }

        if($item->appointment_duration) {
            $des .= "Duration: " . $item->appointment_duration . "\n";
        }

        if($item->price_2) {
            $des .= "Price 2: " . $item->price_2 . "\n";
        }

        if($item->price_3) {
            $des .= "Price 3: " . $item->price_3 . "\n";
        }

        if($item->quoted_amount) {
            $des .= "Quoted Amount: " . $item->quoted_amount . "\n";
        }

        if($item->source_type) {
            $des .= "Source Type: " . $item->source_type . "\n";
        }
        return $des;
    }

    private function setLocation($item)
    {
        $location = [];
        if($item->address) {
            $location[] = $item->address;
        }

        if($item->city) {
            $location[] = $item->city;
        }

        if($item->state) {
            $location[] = $item->state;
        }

        if($item->county) {
            $location[] = $item->county;
        }

        if($item->zip) {
            $location[] = $item->zip;
        }
        return implode(', ', $location);

    }

    /**
     * save result options which are not exists.
     *
     * @param AfAppointment $item
     * @return void
     */
    private function saveResultOptions()
    {
        $repo = App::make('App\Repositories\AppointmentResultOptionRepository');
        $builder = $repo->getBuilder();
        $list = $builder->get();

        $apBuilder = new AfAppointment;
        if($list->lists('name')) {
            $apBuilder = $apBuilder->whereNotIn('result', $list->lists('name'));
        }

        $resultOptions = $apBuilder->groupBy('result')->lists('result');
        if(!$resultOptions) {
            return true;
        }
        foreach ($resultOptions as $option) {
            $fields = [
                [
                    'name' => 'Info',
                    'type' => 'text'
                ]
            ];

            $repo->saveOrUpdate($option, $fields);
        }
        return true;
    }

    private function attachAppointmentResultOptions($appointment, $item)
    {
        $options = json_decode($item->options, true);
        $result1 = $item->result_1;
        $result2 = ine($options, 'i360_result_2_c') ? $options['i360_result_2_c'] : null;
        $result3 = ine($options, 'i360_result_3_c') ? $options['i360_result_3_c'] : null;

        $resultDetail1 = ine($options, 'i360_result_detail_1_c') ? $options['i360_result_detail_1_c'] : null;
        $resultDetail2 = ine($options, 'i360_result_detail_2_c') ? $options['i360_result_detail_2_c'] : null;
        $resultDetail3 = ine($options, 'i360_result_detail_3_c') ? $options['i360_result_detail_3_c'] : null;

        $optionName = $result1;
        $optionValue = $resultDetail1;

        if(!$optionName) {
            $optionName = $result2;
            $optionValue = $resultDetail2;
        }

        if(!$optionName) {
            $optionName = $result3;
            $optionValue = $resultDetail3;
        }

        if(!$optionName) {
            return false;
        }

        $resultOptionIds = [];
        $resultItem = null;
        foreach ($this->resultOptions as $ro) {
            $resultOptionIds[] = $ro->id;
            if($optionName == $ro->name) {
                $resultItem = $ro;
            }
        }

        $fields = $resultItem['fields'];

        if(!$fields) {
            return true;
        }
        $fields[0]['value'] = $optionValue;

        $requestParams = [
            'result_option_id' => $resultItem->id,
            'result' => $fields,
            'result_option_ids' => $resultOptionIds
        ];
        $service = App::make('App\Services\Appointments\AppointmentService');
        $service->addResult($appointment, $requestParams);
        return true;
    }
}