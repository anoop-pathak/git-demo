<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Cache;

class Settings
{
    protected $settings = [];
    protected $settingsList = [];
    protected $companyScopeId; //company id
    protected $user;// user id

    public function __construct($forUser = null, $companyScopeId = null)
    {
        $this->setUser($forUser);
        $companyScope = App::make(\App\Services\Contexts\Context::class);
        if ($companyScope->has()) {
            $this->companyScopeId = $companyScope->id();
        } else {
            $this->companyScopeId = $companyScopeId;
        }
        $this->settings = $this->getAllSettings();
        $this->settingsList = $this->settingsList();
    }

    /**
     * Get For Specific user
     * @param  int $userId | User Id
     * @param  int $companyScopeId | Company Id
     * @return object
     */
    public function forUser($userId = null, $companyScopeId = null)
    {
        return new self($userId, $companyScopeId);
    }

    /**
	 * Get settings by keys
	 * @param  array  $keys Keys
	 * @return Setting
	 */
	public function getSettingByKeys($keys = [])
	{
		foreach ($this->settings as $key => $setting) {
			if(!in_array($setting['key'], $keys)) unset($this->settings[$key]); continue;
		}
 		return array_values($this->settings);
	}

    /**
     * All settings data array
     * @return [type] [description]
     */
    public function all()
    {
        return $this->settings;
    }

    /**
     * All settings lists (key value)
     * @return [type] [description]
     */
    public function pluck()
    {
        return $this->settingsList;
    }

    /**
     * Get a value of a specific setting with key
     * @param  [type] $key [description]
     * @return value
     */
    public function get($key)
    {
        if (!$this->exists($key, $this->settingsList)){
            return;
        }
        return $this->settingsList[$key];
    }

    /**
     * Check Settings exists or not
     * @param  string $key | Setting Key
     * @return boolean
     */
    public function exists($key, $settings)
    {
        return isset($settings[$key]);
    }

    /**
	 * get whole data of a setting by key
	 * @param  string | $key | key of a settings
	 * @return setting array
	 */
	public function getByKey($key)
	{
		$key = arrayCSByValue($this->settings, $key, 'key');
        if(($key === false) || !$this->exists($key, $this->settings)){
            return;
        }

		return $this->settings[$key];
    }

    public function getUser(){
		return $this->user;
    }

    public function getSettingDetailByKey($key)
	{
		return searcharray($key, 'key', $this->settings);
    }

    public function getCacheKey($companyId, $userId)
	{
		return 'settings_'.$companyId.'_'.$userId;
	}

    /******************** Private function ***********************/
    private function settingsList()
    {
        $settings = $this->valideSettings();
        if (empty($settings)) {
            return [];
        }

        $settingsList = $settings->pluck('value', 'key')->toArray();
        $defaultSettingsList = $this->validDefaultSettingsList();
        return array_merge($defaultSettingsList, $settingsList);
    }

    private function getAllSettings()
    {
        $settings = $this->valideSettings();
        if (!is_array($settings)) {
            $settings = $settings->toArray();
        }
        $defaultSettingsArray = $this->defaultSettingsArray();
        return array_merge($defaultSettingsArray, $settings);
    }

    private function defaultSettingsArray()
    {
        $defaultValideSettings = $this->validDefaultSettingsList();

        $ret = [];
        foreach ($defaultValideSettings as $key => $value) {
            $ret[] = [
                'id' => null,
                'name' => str_replace('_', ' ', ucfirst($key)),
                'key' => $key,
                'value' => $value,
            ];
        }
        return $ret;
    }

    private function validDefaultSettingsList()
    {
        $valideSettings = $this->valideSettings();
        if (!is_array($valideSettings)) {
            $valideSettings = $valideSettings->pluck('value', 'key')->toArray();
        }
        $defaultSettings = \config('settings');
        return $defaultSettingsList = array_diff_key($defaultSettings, $valideSettings);
    }

    private function valideSettings()
	{
		$settings = [];
		//if scope not set return empty array..
		if(!$this->companyScopeId) return array();


		$cacheKey = $this->getCacheKey($this->companyScopeId, $this->user);

		$cacheDriver = Cache::getDefaultDriver();
		if(app()->environment() == 'production') {
			$cacheDriver = 'memcached';
		}

		$cache = Cache::driver($cacheDriver);
		if($cache->has($cacheKey)) {
			$settings = $cache->get($cacheKey);
		}

		if(!empty($settings)) return $settings;

		$companySettings = Setting::company($this->companyScopeId)->where(function($query){
				$query->whereNull('user_id')
					->orWhere('user_id','=',0);
			})->pluck('id', 'key')->toArray();

		//users specific settings..
		$userSettings = Setting::company($this->companyScopeId)
			->user($this->user)
			->pluck('id', 'key')->toArray();

		//merge usersSettings keys in company settings..
		$settingIds = array_merge($companySettings, $userSettings);

		$settings = Setting::whereIn('id',$settingIds)
			->select('id','name','key','value', 'user_id', 'company_id')
			->get();

        $cache->put($cacheKey, $settings, config('jp.settings_cache_time'));

		return $settings;
	}

    private function setUser($user = null)
    {
        if ($user) {
            $this->user = $user;
        } else {
            $this->user = Auth::id();
        }
    }
}
