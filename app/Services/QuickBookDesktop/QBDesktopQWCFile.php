<?php namespace App\Services\QuickBookDesktop;

use App\Models\QBDesktopUser;
use App\Models\QuickbookMeta;
use QuickBooks_WebConnector_QWC;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Exceptions\QBOnlineAndDesktopNotAllowedTogetherException;
use App\Services\QuickBooks\Facades\QuickBooks;

class QBDesktopQWCFile
{

    public function downloadFile($companyId)
    {
        if(QuickBooks::isConnected()) {

            throw new QBOnlineAndDesktopNotAllowedTogetherException(trans('response.error.quickbook_not_allowed_together'));
        }

        $runEveryNSecond = config('QBDesktop.run_in_every_second');
        $name = config('QBDesktop.name');
        $descrip = config('QBDesktop.description');
        $appurl = URL::route('quickbook_web_connector_url');
        $supportUrl = config('QBDesktop.app_support_url');
        $username = Auth::user()->email;
        $password = substr(uniqid(), 0, config('QBDesktop.password_limit'));

        $qbUser = QBDesktopUser::where('company_id', $companyId)->first();
        if ($qbUser) {
            $username = $qbUser->qb_username;
            DB::table('quickbooks_user')->where('company_id', $companyId)->delete();
            DB::table('financial_categories')->where('company_id', $companyId)->update(['qb_desktop_id' => null]);
            QuickbookMeta::where('qb_desktop_username', $username)->delete();
            // DB::table('quickbooks_queue')
            //     ->where('qb_username', $username)
            //     ->delete();
            // DB::table('quickbooks_ticket')
            //     ->where('qb_username', $username)
            //     ->delete();
            // DB::table('quickbooks_uom')->where('company_id', $companyId)->delete();
        }

        QBDesktopUtilities::createUser(QBDesktopUtilities::dsn(), $username, $password);
        QBDesktopUtilities::createPaymentMethods($username, $companyId);
        QBDesktopUtilities::createServiceProduct($username, $companyId);
        QBDesktopUtilities::createAccount($username, $companyId);
        QBDesktopUtilities::createDiscountItem($username, $companyId);

        QBDesktopUser::where('qb_username', $username)->update([
            'company_id' => getScopeId(),
            'password_key' => Crypt::encrypt($password)
        ]);

        $fileid = QuickBooks_WebConnector_QWC::fileID();
        $ownerid = QuickBooks_WebConnector_QWC::ownerID();

        $qbtype = QUICKBOOKS_TYPE_QBFS; // You can leave this as-is unless you're using QuickBooks POS

        $readonly = false; // No, we want to write data to QuickBooks

        // Generate the XML file
        $QWC = new \QuickBooks_WebConnector_QWC($name, $descrip, $appurl, $supportUrl, $username, $fileid, $ownerid, $qbtype, $readonly, $runEveryNSecond);
        $xml = $QWC->generate();

        // Send as a file download
        header('Content-type: text/xml');
        header('Content-Disposition: attachment; filename=JobProgress.qwc');
        print($xml);
        exit;
    }
}
