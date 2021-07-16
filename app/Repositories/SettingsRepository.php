<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Services\Contexts\Context;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Repositories\JobPriceRequestRepository;
use App;
use Cache;

class SettingsRepository extends ScopedRepository
{

    /**
     * The base eloquent Setting
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Setting $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function saveSetting($data)
    {
        if (isset($data['id']) && !empty($data['id'])) {
            $setting = $this->updateSetting($data['id'], $data);
        } else {
            $setting = $this->createSetting($data);
        }

        // clear settings cache for a company..
		$this->clearCache();

        return $setting;
    }

    public function createSetting($data)
    {
        $this->deleteDuplicate($data);
        if ($this->scope->has()) {
            $data['company_id'] = $this->scope->id();
        }

        if($data['key'] == 'QUICKBOOK_ONLINE');{
			if(ine($data['value'], 'terms_acceptance')){
				DB::table('quickbooks_two_way_terms_acceptance')->insert([
					'company_id' => getScopeId(),
					'user_id' => Auth::user()->id,
					'terms_acceptance' => $data['value']['terms_acceptance'],
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				]);
				unset($data['value']['terms_acceptance']);
			}
        }

        $setting = $this->model;
        $setting = $setting->create($data);
        return $setting;
    }

    public function updateSetting($id, $data)
    {
        $setting = $this->getById($id);
        $setting->update($data);
        if(($setting->key == 'ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE')
			&& !((bool)$setting->value)) {
			$app = App::make(JobPriceRequestRepository::class);
			$app->markAllInactive();
		}
        return $setting;
    }

    public function getSettings($filters = [])
    {
        $settings = $this->make();
        $this->applyFilter($settings, $filters);
        return $settings;
    }

    public function getByUserId($userId, $key)
    {
        $settings = $this->make();
        $setting = $settings->where('key', $key)
            ->whereUserId($userId)
            ->orderBy('id', 'desc')
            ->first();

        return $setting;
    }


    /************************ Private Section *************************/
    private function applyFilter($query, $filters)
    {
        if (ine($filters, 'key')) {
            $query->where('key', '=', $filters['key']);
        }

        if (ine($filters, 'user_id')) {
            $query->where('user_id', '=', $filters['user_id']);
        }
    }

    private function deleteDuplicate($setting)
    {
        $settings = $this->make()->where('key', $setting['key']);
        if ($this->scope->has()) {
            $settings->whereCompanyId($this->scope->id());
        } else {
            $settings->where('company_id', '=', 0);
        }
        if (ine($setting, 'user_id')) {
            $settings->whereUserId($setting['user_id']);
        } else {
            $settings->where(function ($query) {
                $query->whereNull('user_id')->orWhere('user_id', '=', 0);
            });
        }
        $settings->delete();
    }

    private function clearCache()
	{
		$companyId = $this->scope->id();
		$companyUserList = User::whereCompanyId($companyId)
			->loggable(true)
            ->pluck('id')
            ->toArray();

            $cacheDriver = Cache::getDefaultDriver();
            if(app()->environment() == 'production') {
                $cacheDriver = 'memcached';
            }

            foreach ($companyUserList as $userId) {
                $cacheKey = \Settings::getCacheKey($companyId, $userId);
                Cache::driver($cacheDriver)->forget($cacheKey);
            }

            $companyCacheKey = \Settings::getCacheKey($companyId, null);
            Cache::driver($cacheDriver)->forget($companyCacheKey);
	}
}
