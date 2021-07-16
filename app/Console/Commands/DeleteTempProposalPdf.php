<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DeleteTempProposalPdf extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:delete_temp_proposal_pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Temp Proposal pdf files.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = base_path() . '/' . config('jp.BASE_PATH') . 'temp/';

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                $filelastmodified = filemtime($path . $file);
                if ($file == "." || $file == "..") {
                    continue;
                }

                //24 hours in a day * 3600 seconds per hour
                if ((time() - $filelastmodified) > 24 * 3600) {
                    unlink($path . $file);
                }
            }

            closedir($handle);
        }
    }
}
