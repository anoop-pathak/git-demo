<?php

namespace App\Console\Commands;

use App\Models\Resource;
use Illuminate\Console\Command;

class InvoiceAttachmentCorrectionCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:invoice_attachment_correction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job Invoice Attachment issue due to null mime_type';

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
        // set mime type pdf where null for files..
        Resource::where('is_dir', 0)->whereNull('mime_type')
            ->update(['mime_type' => 'application/pdf']);
    }
}
