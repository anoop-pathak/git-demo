<?php

namespace App\Services\QuickBookDesktop\Setting;

use App\Models\QBDesktopUser;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Settings as CompanySettings;

class Settings
{

    private $settings = null;

    public function getSettings($companyId)
    {
        $settings = CompanySettings::forUser(null, $companyId);

        $this->settings = $settings->get('QUICKBOOK_ONLINE');

        return $this->settings;
    }

    public function getQuickBookContextUser($companyId)
    {
        // Set company scope so that settings can work properly.
        setScopeId($companyId);

        $settings = $this->getSettings($companyId);

        $userId = null;

        if (ine($settings, 'context')) {
            $userId = $settings['context'];
        }

        $user = User::where('company_id', $companyId)
            ->where('id', $userId)
            ->first();

        if (!$user) {

            $user = User::where('company_id', $companyId)
                ->orderBy('created_at', 'asc')
                ->first();
        }

        return $user;
    }

    /**
     * Set Company and User scope
     *  If user context is not set then first user of the company will be used as default user
     */

    public function setCompanyScope($userName = null, $companyId = null)
    {
        $quicbooks = null;

        if ($userName && !$companyId) {
            $quicbooks =  QBDesktopUser::where('qb_username', $userName)->first();
        }

        if (!$quicbooks && $companyId) {

            $quicbooks = QBDesktopUser::where('company_id', $companyId)->first();
        }

        if (!$quicbooks) {
            return false;
        }

        if ($quicbooks) {

            // If we have any existing session
            Auth::logout();

            $user = $this->getQuickBookContextUser($quicbooks->company_id);

            if (!$user) {

                throw new Exception('Company Context is not found!');
            };

            setAuthAndScope($user->id);

            return true;
        }

        return false;
    }

    public function getUser($userName)
    {
        return QBDesktopUser::where('qb_username', $userName)->first();
    }

    public function isControlledSyncEnabled()
    {
        $settings = $this->getSettings(getScopeId());

        if (ine($settings, 'controlled_sync') && $settings['controlled_sync'] == 'true') {

            return true;
        }

        return false;
    }
}
