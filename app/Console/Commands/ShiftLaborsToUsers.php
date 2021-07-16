<?php

namespace App\Console\Commands;

use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\Labour;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShiftLaborsToUsers extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:labors-to-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shift Labors To Users';

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
        $labours = Labour::whereNull('user_id')->get();

        DB::beginTransaction();
        try {
            foreach ($labours as $labour) {
                $data['user'] = $this->mapUserData($labour->toArray());
                $data['profile'] = $this->mapUserProfileData($labour->toArray());
                $this->shiftToUser($labour, $data);
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->error($e->getMessage());
        }
        DB::commit();
    }

    private function shiftToUser($labour, $data)
    {
        $user = User::create($data['user']);
        $profile = new UserProfile($data['profile']);
        $user->profile()->save($profile);

        $labour->user_id = $user->id;
        $labour->save();
        $this->saveFinancialDetails($user, $labour->toArray());
        $this->resignedToJobs($labour, $user->id);

        return $user;
    }

    private function mapUserData($input)
    {
        $map = [
            'company_id',
            'first_name',
            'last_name',
            'email',
            'password',
            'type',
            'company_id',
            'active' => 'is_active',
            'resource_id',
        ];

        $data = $this->mapInputs($map, $input);
        if ($input['type'] === 'sub_contractor') {
            $data['group_id'] = User::GROUP_SUB_CONTRACTOR;
        } else {
            $data['group_id'] = User::GROUP_LABOR;
        }
        return $data;
    }

    private function mapUserProfileData($input)
    {
        $map = [
            'user_id',
            'address',
            'address_line_1',
            'city',
            'state_id',
            'zip',
            'country_id',
            'additional_phone' => 'phones',
            'profile_pic',
        ];

        $data = $this->mapInputs($map, $input);
        return $data;
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map, $input)
    {
        $ret = [];
        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : "";
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : "";
            }
        }

        return $ret;
    }

    private function saveFinancialDetails($labour, $input)
    {
        $companyId = $labour->company_id;
        $category = FinancialCategory::whereCompanyId($companyId)
            ->whereName('LABOR')
            ->first();
        if (!$category) {
            $category = FinancialCategory::create(['company_id' => $companyId, 'name' => 'LABOR']);
        }

        $data = [
            'name' => $labour->first_name . ' ' . $labour->last_name,
            'company_id' => $companyId,
            'labor_id' => $labour->id,
            'category_id' => $category->id,
            'unit' => isset($input['commission_unit']) ? $input['commission_unit'] : "",
            'unit_cost' => isset($input['commission_rate']) ? $input['commission_rate'] : "",
            'code' => isset($input['code']) ? $input['code'] : "",
        ];

        $financial = FinancialProduct::whereLaborId($labour->id)->first();
        if ($financial) {
            $financial->update($data);
        } else {
            FinancialProduct::create($data);
        }
    }

    private function resignedToJobs($labour, $newId)
    {
        if ($labour->type == Labour::LABOUR) {
            DB::table('job_labour')->where('labour_id', $labour->id)
                ->update(['labour_id' => $newId]);
        } else {
            DB::table('job_sub_contractor')->where('sub_contractor_id', $labour->id)
                ->update(['sub_contractor_id' => $newId]);
        }
    }
}
