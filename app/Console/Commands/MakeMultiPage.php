<?php

namespace App\Console\Commands;

use App\Models\Estimation;
use App\Models\EstimationPage;
use App\Models\Proposal;
use App\Models\ProposalPage;
use App\Models\Template;
use App\Models\TemplatePage;
use Illuminate\Console\Command;

class MakeMultiPage extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:make-multipage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $type = $this->ask(" 1. Templates \n 2. Estimates \n 3. Proposals \n select option : ");
        switch ($type) {
            case '1':
                $this->multipageTemplate();
                break;

            case '2':
                $this->multipageEstimates();
                break;

            case '3':
                $this->multipageProposals();
                break;

            default:
                $this->info(' Invalid Option');
                break;
        }
    }

    private function multipageTemplate()
    {
        $templates = Template::has('pages', '=', 0)->get();
        foreach ($templates as $key => $template) {
            TemplatePage::create([
                'template_id' => $template->id,
                'content' => $template->content,
                'editable_content' => $template->editable_content,
                'thumb' => $template->thumb,
                'order' => 1,
            ]);
        }
        $this->info(' Done');
    }

    private function multipageEstimates()
    {
        $estimations = Estimation::has('pages', '=', 0)->get();
        foreach ($estimations as $key => $estimation) {
            EstimationPage::create([
                'estimation_id' => $estimation->id,
                'template' => $estimation->template,
                'template_cover' => $estimation->template_cover,
                'image' => $estimation->image,
                'order' => 1,
            ]);
        }
        $this->info(' Done');
    }

    private function multipageProposals()
    {
        $proposals = Proposal::has('pages', '=', 0)->get();
        foreach ($proposals as $key => $proposal) {
            ProposalPage::create([
                'proposal_id' => $proposal->id,
                'template' => $proposal->template,
                'template_cover' => $proposal->template_cover,
                'image' => $proposal->image,
                'order' => 1,
            ]);
        }
        $this->info(' Done');
    }
}
