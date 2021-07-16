<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Proposal;
use Carbon\Carbon;
use FlySystem;
use Illuminate\Support\Facades\DB;

class UpdateProposalTempltePdf extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_proposal_template_pdf';

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
	public function fire()
	{
		$forAll = $this->confirm("Are you want to update all the proposals? [Y|N]");

		if(!$forAll) {
			$proposalIds = $this->ask("Please enter proposal id.");
		}else {
			$proposalIds = config('jp.precision_company_proposals');
		}

		$startedAt = Carbon::now()->toDateTimeString();

		$this->info('Command started at: '.$startedAt);

		$proposals = Proposal::whereIn('id', (array)$proposalIds)
			->get();

		foreach ($proposals as $proposal) {
			$proposal = $this->updatePdf($proposal);

			foreach ($proposal->pages as $page) {
				$this->updateThumb($page, $proposal->page_type, $proposal);
			}

			$this->info("Proposal ID completed: ".$proposal->id);
		}

		$endedAt = Carbon::now()->toDateTimeString();
		$this->info('Command completed at: '.$endedAt);
	}

	private function updatePdf($proposal)
	{
		$existingFile = null;
		if(!empty($proposal->file_path)) {
			$existingFile = config('jp.BASE_PATH').$proposal->file_path;
		}
		$proposal = Proposal::with('pages','attachments')->find($proposal->id);
		$filename  = $proposal->id.'_'.Carbon::now()->timestamp.rand().'.pdf';
		$baseName = 'proposals/' . $filename;
		$fullPath = config('jp.BASE_PATH').$baseName;

		$pageHeight = '23.9cm';

		if($proposal->page_type == 'legal-page') {
			$pageHeight = '28.6cm';
		}

		$job 	  = $proposal->job;
		$customer = $job->customer;

		$pdf = \PDF::loadView('proposal.multipages', [
			'pages' 	  => $proposal->pages,
			'pageType'    => $proposal->page_type,
			'attachments' => $proposal->attachments,
			'attachments_per_page' => $proposal->attachments_per_page,
			'company'			   => $proposal->company,
			'job'				   => $job,
			'customer'			   => $customer,
			'proposal'			   => $proposal,
			])
			->setOption('page-size','A4')
			->setOption('margin-left',0)
			->setOption('margin-right',0)
			->setOption('margin-top','0.5cm')
			->setOption('margin-bottom','0.5cm')
			->setOption('page-width','16.8cm')
			->setOption('page-height', $pageHeight);

		$mimeType = 'application/pdf';
		FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

		DB::table('proposals')
			->where('id', $proposal->id)
			->update([
				'file_path' => $baseName,
				'file_size' => FlySystem::getSize($fullPath),
			]);

		return $proposal;
	}

	private function updateThumb($page, $pageType, $proposal)
	{
		$contents = \View::make('proposal.proposal', [
			'proposal' => $proposal,
			'page' => $page,
			'pageType' => $pageType
		])->render();

		$filename  = Carbon::now()->timestamp.rand().'.jpg';
		$imageBaseName = 'proposals/' . $filename;
		$thumbBaseName = 'proposals/thumb/' . $filename;
		$imageFullPath = config('jp.BASE_PATH').$imageBaseName;
		$thumbFullPath = config('jp.BASE_PATH').$thumbBaseName;

		$snappy = \App::make('snappy.image');
		$snappy->setOption('width', '794');
		if($pageType == 'legal-page') {
			$snappy->setOption('height', '1344');
		}else {
			$snappy->setOption('height', '1122');
		}
		$image = $snappy->getOutputFromHtml($contents);

		FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);

		$image = \Image::make($image);
		if($image->height() > $image->width()) {
			$image->heighten(250, function($constraint) {
		    	$constraint->upsize();
		   	});
		}else {
		    $image->widen(250, function($constraint) {
		       $constraint->upsize();
		    });
		}

		FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

		DB::table('proposal_pages')
			->where('id', $page->id)
			->update([
				'image' => $imageBaseName,
				'thumb' => $thumbBaseName,
			]);

		return $page;
	}
}
