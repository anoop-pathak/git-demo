<?php

namespace App\Console\Commands;

use App\Models\QBDesktopUser;
use App\Models\QuickbookMeta;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QBDesktopAddQueue extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:qb_desktop_add_queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'QBDesktopAddQueue.';

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
        DB::table('quickbook_meta')->truncate();
        DB::table('quickbooks_queue')->truncate();

        $qbdUsers = QBDesktopUser::whereSetupCompleted(true)->get();
        foreach ($qbdUsers as $qbdUser) {
            $user = $qbdUser->qb_username;

            QBDesktopUtilities::createPaymentMethods($user);
            QBDesktopUtilities::createServiceProduct($user);
            QBDesktopUtilities::createAccount($user);

            $qmd = QuickbookMeta::where('qb_desktop_username', $user)
                ->get();
            foreach ($qmd as $qm) {
                switch ($qm->type) {
                    case QBDesktopUtilities::SERVICE_PRODUCT:
                        QBDesktopQueue::addServiceItem($qm->id, $user);
                        break;
                    case QBDesktopUtilities::PAYMENT_METHOD:
                        QBDesktopQueue::addPaymentMethod($qm->id, $user);
                        break;
                    case QBDesktopUtilities::ACCOUNT:
                        QBDesktopQueue::addAccount($qm->id, $user);
                        break;
                }
            }
        }
    }
}
