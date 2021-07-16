<?php

namespace App\Services;

use App\Events\ProposalCreated;
use App\Events\ShareProposalStatus;
use App\Exceptions\InvalidAttachmentException;
use App\Helpers\ProposalPageAutoFillDbElement;
use App\Models\Estimation;
use App\Models\Proposal;
use App\Models\ProposalAttachment;
use App\Models\ProposalPage;
use App\Models\TempProposalPage;
use App\Repositories\JobRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\TemplatesRepository;
use FlySystem;
use App\Services\Google\GoogleSheetService;
use App\Services\Worksheets\WorksheetsService;
use Carbon\Carbon;
use Exception;
use PDF;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use App\Exceptions\InvalidFileException;
use Settings;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobProposalStoreFile;
use App\Models\PageTableCalculation;
use App\Services\Folders\FolderService;
use App\Models\Folder;
use GuzzleHttp\Client;
use App\Models\Company;
use App\Exceptions\Proposal\ProposalCannotBeUpdate;
use App\Services\DigitalSignature;
use JobQueue;
use App\Exceptions\Proposal\ProposalAlreadySignedDigitally;
use App\Exceptions\Proposal\ProposalSignatureNotExist;
use App\Exceptions\Proposal\ProposalStatusMustBeAccepted;
use App\Exceptions\Queue\JobAlreadyInQueueException;
use DataMasking;

class ProposalService
{

    public function __construct(ProposalsRepository $repo, WorksheetsService $worksheetsService, GoogleSheetService $googleSheetService, TemplatesRepository $templateRepo, JobRepository $jobRepo, FileService $fileService)
    {
        $this->repo = $repo;
        $this->worksheetsService = $worksheetsService;
        $this->googleSheetService = $googleSheetService;
        $this->templateRepo = $templateRepo;
        $this->jobRepo = $jobRepo;
        $this->fileService = $fileService;
    }

    /**
     * Save proposal by pages
     * @param  Int $jobId job id
     * @param  array $meta meta info
     * @return Proposal
     */
    public function saveProposalByPages($job, $meta = [])
    {
        $pagesData = [];
        $title = null;

        $meta['serial_number'] = $this->repo->generateSerialNumber();
        $pageAutoFill = new ProposalPageAutoFillDbElement;
        $pageAutoFill->setAttributes($job, $meta['serial_number']);

        //get template page contents
        foreach ($meta['pages'] as $page) {
            if ($page['type'] == 'temp_proposal_page') {
                $pageData = TempProposalPage::whereId($page['id'])->firstOrFail();
                $content = $pageAutoFill->fillSerialNumberElement($pageData->content);
            } else {
                $pageData = $this->templateRepo->getPageById($page['id']);
                $content = $pageAutoFill->fillTemplate($pageData->content);
            }

            $pagesData[] = [
                'template' => $content,
                'title' => $pageData->title,
                'auto_fill_required' => $pageData->auto_fill_required,
                'tables' => $pageData->pageTableCalculations
            ];
        }
        $proposal = $this->create($job->id, $pagesData, $createdBy = \Auth::id(), $meta);

        $proposal = $this->repo->getProposalWithoutPageContents($proposal->id);

        return $proposal;
    }


    /**
     * Update propsal by pages
     * @param  Instance $proposal Proposal
     * @param  Array $meta meta info
     * @return Proposal
     */
    public function updateProposalByPages($proposal, $meta)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
        }

        $pagesData = [];

        $job = $proposal->job;
        $pageAutoFill = new ProposalPageAutoFillDbElement;
        $pageAutoFill->setAttributes($job, $proposal->serial_number);

        //get template page contents
        foreach ($meta['pages'] as $key => $page) {
            //get page
            switch ($page['type']) {
                case 'temp_proposal_page':
                    $pageData = TempProposalPage::whereId($page['id'])->firstOrFail();
                    $content = $pageAutoFill->fillSerialNumberElement($pageData->content);
                    break;
                case 'template_page':
                    $pageData = $this->templateRepo->getPageById($page['id']);
                    $content = $pageAutoFill->fillTemplate($pageData->content);
                    break;
                default:
                    $pageData = ProposalPage::whereId($page['id'])->firstOrFail();
                    $content = $pageAutoFill->fillSerialNumberElement($pageData->template);
                    break;
            }

            $meta['pages'][$key] = [
                'template' => $content,
                'title' => $pageData->title,
                'auto_fill_required' => $pageData->auto_fill_required,
                'tables' => $pageData->pageTableCalculations
            ];
        }

        //update proposal
        $proposal = $this->update($proposal, $meta);

        $proposal = $this->repo->getProposalWithoutPageContents($proposal->id);

        return $proposal;
    }

    /**
     * Create Proposal
     * @param  int $jobId | Job Id
     * @param  array $pages | Pages
     * @param  int $createdBy | Createby User Id
     * @param  array $meta | Meta Data
     * @return Proposal
     */
    public function create($jobId, $pages, $createdBy, $meta = [])
    {
        DB::beginTransaction();
        try {
            $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_PROPOSAL);
			}
            $proposal = $this->repo->saveProposal($jobId, $createdBy, $meta);
            foreach ((array)$pages as $key => $page) {
                $template = isset($page['template']) ? $page['template'] : "";
                $order = $key + 1;
                $this->createPage($proposal, $template, $order, $page);
            }
            if (ine($meta, 'save_as')) {
                //get destincation proposal
                $destProposal = $this->repo->getById($meta['save_as']);
                $this->copyAttachments($destProposal, $proposal, $meta);
            }
            $this->attachments($proposal, $meta);
            $proposal = $this->createPdf($proposal);

            $eventData = [
				'reference_id' => $proposal->id,
				'job_id' => $jobId,
				'name' => $proposal->id,
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        //proposal created event..
        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));

        return $proposal;
    }

    public function createGoogleSheet($jobId, $input = [])
    {
        DB::beginTransaction();
        try {
            $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_PROPOSAL);
			}
            $createdBy = \Auth::id();
            //create proposal..
            $proposal = $this->repo->saveProposal($jobId, $createdBy, $input);

            $eventData = [
				'reference_id' => $proposal->id,
				'job_id' => $jobId,
				'name' => $proposal->id,
				'parent_id' => $parentId,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));

            if (isset($input['file'])) {
                $sheetId = $this->googleSheetService->uploadFile(
                    $input['file'],
                    $proposal->title
                );
            } elseif (isset($input['google_sheet_id'])) {
                $sheetId = $this->googleSheetService->createFromExistingSheet(
                    $input['google_sheet_id'],
                    $proposal->title
                );
            } else {
                $sheetId = $this->googleSheetService->createEmptySpreadSheet($proposal->title);
            }

            $proposal->google_sheet_id = $sheetId;

            $proposal->save();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        //proposal created event..
        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));

        return $proposal;
    }

    /**
     * Update Proposal
     * @param  Proposal $proposal | Proposal
     * @param  array $data | Data
     * @return [type]             [description]
     */
    public function update(Proposal $proposal, $input)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
        }

        DB::beginTransaction();
        try {
            foreach ((array)$input['pages'] as $key => $page) {
                if (ine($page, 'template')) {
                    continue;
                }
                throw new Exception(\Lang::get('response.error.template_content_empty'));
            }

            $pageAutoFill = new ProposalPageAutoFillDbElement;
            ;
            if (ine($input, 'delete_attachments')) {
                ProposalAttachment::whereIn('id', $input['delete_attachments'])
                    ->whereProposalId($proposal->id)
                    ->delete();
            }
            $proposal->update($input);
            $existingPages = $proposal->pages;

            foreach ((array)$input['pages'] as $key => $page) {
                $template = isset($page['template']) ? $page['template'] : "";
                if ($template && !empty($input['data_elements'])) {
                    $template = $pageAutoFill->fillTemplateValue($template, $input['data_elements']);
                }

                if(ine($page, 'id')) {
					$getPage = ProposalPage::where('id', $page['id'])->first();
					if($getPage) {
						$page['tables'] = $getPage->pageTableCalculations;
					}
				}

                $order = $key + 1;
                $this->createPage($proposal, $template, $order, $page);
            }
            if ($existingPages->count()) {
                foreach ($existingPages as $page) {
                    $this->deletePage($page);
                }
            }
            $this->attachments($proposal, $input);
            $proposal = $this->createPdf($proposal);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();
        return $proposal;
    }

    /**
     * Create Propsal Page
     * @param  Proposal $proposal | Proposal
     * @param  string $template | Html content
     * @param  int $order | Order
     * @param  array $meta | Additional Data
     * @return [type]                 [description]
     */
    public function createPage(Proposal $proposal, $template, $order, $meta = [])
    {
        $page = ProposalPage::create([
            'proposal_id' => $proposal->id,
            'template' => $template,
            'template_cover' => ine($meta, 'template_cover') ? $meta['template_cover'] : null,
            'order' => ine($meta, 'order') ? $meta['order'] : $order,
            'title' => ine($meta, 'title') ? $meta['title'] : null,
            'auto_fill_required' => ine($meta, 'auto_fill_required') ? $meta['auto_fill_required'] : null,
        ]);

        $thumb = $this->createThumb($page, $proposal->page_type, $proposal);

		if(ine($meta, 'tables')) {
			foreach ($meta['tables'] as $table) {
				$pageTableCalculation = PageTableCalculation::create([
					'page_type' =>  PageTableCalculation::PROPOSAL_PAGE_TYPE,
					'type_id' => $page->proposal_id,
					'page_id' => $page->id,
					'name'	  => isset($table['name']) ? $table['name'] : null,
					'ref_id'  => $table['ref_id'],
					'head'    => $table['head'],
					'body'    => $table['body'],
					'foot'    => $table['foot'],
					'options' => ine($table, 'options') ? $table['options']  : []
				]);
			}
		}
    }

    /**
     * Delete Page
     * @param  Proposal
     * @return [type]               [description]
     */
    public function deletePage(ProposalPage $page)
    {
        if (!empty($page->image)) {
            $filePath = config('jp.BASE_PATH') . $page->image;
            FlySystem::delete($filePath);
        }

        if (!empty($page->thumb)) {
            $filePath = config('jp.BASE_PATH') . $page->thumb;
            FlySystem::delete($filePath);
        }

        $existingPageCalculations = $page->pageTableCalculations()->delete();

        $page->delete();

        return true;
    }

    /**
     * Create pdf
     * @param  Proposal $proposal | Proposal Object
     * @return [type]                 [description]
     */
    public function createPdf(Proposal $proposal)
    {
        $existingFile = null;
        if (!empty($proposal->file_path)) {
            $existingFile = config('jp.BASE_PATH') . $proposal->file_path;
        }
        $proposal = Proposal::with('pages', 'attachments')->find($proposal->id);
        $filename = $proposal->id . '_' . Carbon::now()->timestamp . rand() . '.pdf';
        $baseName = 'proposals/' . $filename;
        $fullPath = config('jp.BASE_PATH') . $baseName;

        $pageHeight = '23.9cm';

        if ($proposal->page_type == 'legal-page') {
            $pageHeight = '28.6cm';
        }

        $job = $proposal->job;
        $customer = $job->customer;

        $pdf = PDF::loadView('proposal.multipages', [
            'pages' => $proposal->pages,
            'pageType' => $proposal->page_type,
            'attachments' => $proposal->attachments,
            'attachments_per_page' => $proposal->attachments_per_page,
            'company' => $proposal->company,
            'job' => $job,
            'customer' => $customer,
            'proposal' => $proposal,
        ])
            ->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', '0.5cm')
            ->setOption('margin-bottom', '0.5cm')
            ->setOption('page-width', '16.8cm')
            ->setOption('page-height', $pageHeight);

        // dd($pdf->output());

        $mimeType = 'application/pdf';
        FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

        $proposal->file_name = $proposal->title . '.pdf';
        $proposal->file_path = $baseName;
        $proposal->file_mime_type = $mimeType;
        $proposal->file_size = FlySystem::getSize($fullPath);
        // $proposal->file_size = 0;
        $proposal->save();

        // delete existing Pdf
        if (!is_null($existingFile)) {
            FlySystem::delete($existingFile);
        }

        return $proposal;
    }


    /**
     * generate Pdf
     * @param  string $pageType a4-page
     * @param  array $pages array of pages
     * @return token
     */
    public function generatePdf($pageType, $pages = [])
    {
        $token = Carbon::now()->timestamp . '_' . rand();
        $fileName = 'temp/' . $token . '.pdf';
        $fullPath = config('jp.BASE_PATH') . $fileName;
        $pageHeight = '23.9cm';

        if ($pageType == 'legal-page') {
            $pageHeight = '28.6cm';
        }

        $pdf = PDF::loadView('proposal.proposal-pdf', [
            'pages' => $pages,
            'pageType' => $pageType,
        ])
            ->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', '0.5cm')
            ->setOption('margin-bottom', '0.5cm')
            ->setOption('page-width', '16.8cm')
            ->setOption('page-height', $pageHeight);

        $pdf->save(public_path('uploads/' . $fileName));

        return $token;
    }

    /**
     * Make a copy of proposal
     * @param  Proposal $destProposal Destincation proposal
     * @param  int $createdBy
     * @return proposal
     *
     */
    public function makeCopy(Proposal $destProposal, $createdBy, $meta = [])
    {
        DB::beginTransaction();
        try {
            $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
			if($parentId) {
				$folderService = app(FolderService::class);
				$parentDir = $folderService->getParentDir($parentId, Folder::JOB_PROPOSAL);
            }

            $proposal = $destProposal->replicate();
            $proposal->created_by = $createdBy;
            $proposal->status = Proposal::DRAFT;
            $proposal->token = null;
            $proposal->file_path = null;
            $proposal->title = ine($meta, 'title') ? $meta['title'] : '';
            $proposal->signature = null;
            $proposal->digital_signed = false;
            $proposal->multiple_signatures = null;
            $proposal->save();

            if (!ine($meta, 'title')) {
                $proposal->generateName();
            }

            switch ($destProposal->type) {
                case Proposal::FILE:
                    $filePath = config('jp.BASE_PATH') . $destProposal->file_path;
                    // get file extension..
                    $extension = File::extension($filePath);
                    $destinationPath = config('jp.BASE_PATH');
                    $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;
                    $basePath = 'proposals/' . $physicalName;
                    // copy file to attachment directory..
                    FlySystem::copy($filePath, $destinationPath . $basePath);

                    if ($destProposal->thumb) {
                        $thumbBasePath = 'proposals/thumb/' . $physicalName;
                        FlySystem::copy(config('jp.BASE_PATH') . $destProposal->thumb, $destinationPath . $thumbBasePath);
                        // save thumb path..
                        $proposal->thumb = $thumbBasePath;
                    }

                    // save data..
                    $proposal->file_path = $basePath;
                    $proposal->save();
                    break;
                case Proposal::WORKSHEET:
                    $destWorksheet = $destProposal->worksheet;
                    $worksheet = $destWorksheet->replicate();
                    $worksheet->name = $proposal->title;
                    $worksheet->file_size = null;
                    $worksheet->file_path = null;
                    $worksheet->thumb = null;
                    $worksheet->order = $this->worksheetsService->getWorksheetOrder([
                        'type' => $destWorksheet->type,
                        'job_id' => $destWorksheet->job_id,
                    ]);
                    $worksheet->save();
                    $proposal->update(['worksheet_id' => $worksheet->id]);
                    foreach ($destWorksheet->finacialDetail as $detail) {
                        $newDetail = $detail->replicate();
                        $newDetail->worksheet_id = $worksheet->id;
                        $newDetail->save();
                    }

                    $this->worksheetsService->createPDF($worksheet, $proposal);
                    break;
                case Proposal::GOOGLE_SHEET:
                    $sheetId = $this->googleSheetService->createFromExistingSheet(
                        $destProposal->google_sheet_id,
                        $proposal->title
                    );
                    $proposal->google_sheet_id = $sheetId;
                    $proposal->save();
                    break;
                default:
                    $this->copyPages($destProposal, $proposal);
                    $this->copyAttachments($destProposal, $proposal);
                    $proposal = $this->createPdf($proposal);
                    break;
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        $eventData = [
			'reference_id' => $proposal->id,
			'name' => $proposal->id,
			'job_id' => $proposal->job_id,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));

        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));

        return $proposal;
    }

    /**
     * Upload file of proposal.
     * @param  Int $jobId Job Id
     * @param  File $file File Data
     * @return Proposal
     */
    public function uploadFile($jobId, $file, $imageBase64 = null, $input = array())
    {
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_PROPOSAL);
        }

        if(ine($input, 'file_url')) {
            $data =  $this->fileService->getDataFromUrl($input['file_url'], $input['file_name']);
            return $this->createFileFromContents(
                $jobId,
                $data['file'],
                $data['file_name'],
                $data['mime_type'],
                $input['file_name']
            );
        }

        if($imageBase64 && !is_file($file)) {
            return $this->uploadBase64($file, $jobId, $input);
        }

        $mimeType = $file->getMimeType();
        $originalName = ine($input, 'file_name') ? addExtIfMissing($input['file_name'], $mimeType) : $file->getClientOriginalName();

        if (ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $originalName = substr($originalName, 0, strpos($originalName, '.')) . '.pdf';
            $mimeType = 'application/pdf';
            $physicallyName = generateUniqueToken() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'proposals/' . $physicallyName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $imgContent = base64_encode(file_get_contents($file));

            $data = [
                'imgContent' => $imgContent,
            ];

            $content = \view('resources.single_img_as_pdf', $data)->render();

            $pdf = PDF::loadHTML($content)->setPaper('a4')->setOrientation('portrait');
            $pdf->setOption('dpi', 200);

            $uploaded = FlySystem::write($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
            $fileSize = FlySystem::getSize($fullPath);
        } else {
            $physicallyName = generateUniqueToken() . '_' . str_replace(' ', '_', strtolower($originalName));
            $basePath = 'proposals/' . $physicallyName;
            $fullPath = config('jp.BASE_PATH') . $basePath;
            $fileSize = $file->getSize();
            //upload file..
            if (in_array($mimeType, config('resources.image_types'))) {
                $image = \Image::make($file)->orientate();

                if(isTrue(Settings::get('WATERMARK_PHOTO'))) {
                    $image = addPhotoWatermark($image, $jobId);
                }
                $uploaded = FlySystem::put($fullPath, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
            } else {
                $uploaded = FlySystem::writeStream($fullPath, $file, ['ContentType' => $mimeType]);
            }
        }

        if (!$uploaded) {
            return false;
        }

        // save thumb for images..
        $thumbBasePath = null;
        if (!ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'proposals/thumb/' . $physicallyName;
            $thumbPath = config('jp.BASE_PATH') . $thumbBasePath;

            $image = \Image::make($file);
            if ($image->height() > $image->width()) {
                $image->heighten(200, function ($constraint) {
                    $constraint->upsize();
                });
            } else {
                $image->widen(200, function ($constraint) {
                    $constraint->upsize();
                });
            }

            FlySystem::put($thumbPath, $image->encode()->getEncoded());
        }

        $fileName = ine($input, 'file_name') ? $input['file_name'] : $originalName;

        $createdBy = \Auth::id();
        $input['title'] = $fileName;

        //add file data..
        $input['is_file'] = true;
        $input['file_name'] = $fileName;
        $input['file_path'] = $basePath;
        $input['file_mime_type'] = $mimeType;
        $input['file_size'] = $fileSize;
        $input['thumb'] = $thumbBasePath;

        //create proposal..
        $proposal = $this->repo->saveProposal($jobId, $createdBy, $input);

        $eventData = [
			'reference_id' => $proposal->id,
			'job_id' => $jobId,
			'name' => $proposal->id,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));

        //proposal created event..
        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));

        return $proposal;
    }

    /**
     * Create Proposal File from Contents
     * @param  Int $jobId Job Id
     * @param  string $fileContent | Fine contents
     * @param  string $name | File name
     * @param  string $mimeType | Mime type
     * @return Estimation
     */
    public function createFileFromContents($jobId, $fileContent, $name, $mimeType, $fileName = null)
    {
        $physicalName = uniqueTimestamp() . '_' . str_replace(' ', '_', strtolower($name));
        $basePath = 'proposals/' . $physicalName;
        $fullPath = \config('jp.BASE_PATH') . $basePath;

        //save file..
        FlySystem::put($fullPath, $fileContent, ['ContentType' => $mimeType]);

        $fileSize = FlySystem::getSize($fullPath);

        // save thumb for images..
        $thumbBasePath = null;
        if (in_array($mimeType, config('resources.image_types'))) {
            $thumbBasePath = 'proposals/thumb/' . $physicalName;
            $thumbPath = config('jp.BASE_PATH') . $thumbBasePath;

            $image = \Image::make($fileContent);
            if ($image->height() > $image->width()) {
                $image->heighten(200, function ($constraint) {
                    $constraint->upsize();
                });
            } else {
                $image->widen(200, function ($constraint) {
                    $constraint->upsize();
                });
            }

            FlySystem::put($thumbPath, $image->encode()->getEncoded());
        }

        $name = $fileName ? $fileName : $name;

        $createdBy = \Auth::id();
        $data['title'] = $name;

        //add file data..
        $data['is_file'] = true;
        $data['file_name'] = $name;
        $data['file_path'] = $basePath;
        $data['file_mime_type'] = $mimeType;
        $data['file_size'] = $fileSize;
        $data['thumb'] = $thumbBasePath;

        //create estimation..
        $proposal = $this->repo->saveProposal($jobId, $createdBy, $data);

        //proposal created event..
        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));

        return $proposal;
    }

    /**
     * Proposal status update
     * @param  Proposal $proposal Propsoal
     * @param  string $status Proposal Status
     * @return Void
     */
    public function updateStatus(Proposal $proposal, $status, $thankYouEmail = true)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
        }

        $proposal->status = $status;
        $proposal->update();

        // update contract signed date
        if ($proposal->status == Proposal::ACCEPTED
            && ($job = $proposal->job)
            && (!$job->cs_date)) {
            $this->jobRepo->updateContractSignedDate($job, $proposal->updated_at);
        }

        if (in_array($proposal->status, ['accepted', 'rejected', 'viewed'])) {
            Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ShareProposalStatus', new ShareProposalStatus($proposal, $thankYouEmail));
        }
    }

    /**
     * Get By ID
     * @param  int $id | Proposal Id
     * @return [type]     [description]
     */
    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
     * Prposal Rename
     * @param  Instance $proposal Prposal Instance
     * @param  String $title Proposal Title
     * @return Proposal
     */
    public function rename($proposal, $title)
    {
        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            $this->googleSheetService->renameSpreadSheet($proposal->google_sheet_id, $title);
        }

        $proposal->title = $title;

        if ($proposal->is_file) {
            $proposal->file_name = $title;
        }

        $proposal->save();

        //worksheet name rename
        if ($worksheet = $proposal->worksheet) {
            $worksheet->name = $title;
            $worksheet->save();
        }

        return $proposal;
    }

    /**
     * get eamil content
     * @return content
     */
    public function getEmailContent($proposal)
    {
        /*get data for email content*/
        $data['url'] = config('app.url') . config('jp.BASE_PROPOSAL_PATH') . $proposal->token . '/view';
        $data['customer_name'] = $proposal->job->customer->first_name;
        $data['companyName'] = $proposal->job->company->name;

        /*set email content*/
        $content = '<p>Hi ' . $data['customer_name'] . ',</p><p>' . "We've" . ' included the estimate we discussed. Please let us know if you have any questions.<br>When you are ready to proceed, click accept, then sign it electronically.<br>We will be notified and get your project on our schedule!</p><p>&nbsp;</p><p style="text-align:left;padding-top:2px;padding-bottom:10px;"><a ref="public-button" href=' . $data['url'] . ' style="text-decoration:none;color: #FFF;background-color: #337AB7;border: 1px solid #2E6DA4;border-radius: 4px;padding: 6px 12px;text-align: center;white-space: nowrap;">Click Here to View</a></p><p>&nbsp;</p><p style="margin-bottom:0px;">Thanks</p><p style="margin-top:7px;">' . $data['companyName'] . '</p>';

        return $content;
    }

    /**
     * Proposal Accept
     * @param  Instance $proposal Proposal
     * @param  Araay $input Array of input
     * @return [type]           [description]
     */
    public function updateSharedProposal($proposal, $input)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
		}

        if (!$proposal->is_file) {
            if (ine($input, 'template')) {
                $input['pages'][0]['template'] = $input['template'];
                $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
            }
            $input['is_file'] = 0;

            if (ine($input, 'pages')) {
                $this->update($proposal, $input);
            }
        }

        $proposal->status = issetRetrun($input, 'status') ?: $proposal->status;
        $proposal->signature = issetRetrun($input, 'signature') ?: $proposal->signature;
        $proposal->multiple_signatures = issetRetrun($input, 'multiple_signatures') ?: $proposal->multiple_signatures;
        $proposal->comment = issetRetrun($input, 'comment') ?: $proposal->comment;

        if (isset($input['initial_signature'])) {
            $proposal->initial_signature = $input['initial_signature'];
        }

        $proposal->save();

        // update contract signed date
        if($proposal->isAccepted() && ($job = $proposal->job) && (!$job->cs_date)) {
            $this->jobRepo->updateContractSignedDate($job, $proposal->updated_at);
        }

        // update job work crew notes..
        if (!empty($proposal->note)) {
            $job = $proposal->job;
            $note = "Proposal - $proposal->title";
            $note .= ' \n ' . $proposal->note;
            $job->work_crew_notes .= ' \n ' . $note;
            $job->save();
        }

        if ($proposal->isWorksheet()) {
            if(ine($input, 'template_pages')) {
				$this->worksheetsService->saveTemplatePages($proposal->worksheet, $input);
            }else{
				$worksheet = $proposal->worksheet;
				$subWorksheetOldPath = $worksheet->file_path;

				$this->worksheetsService->createPDF($worksheet, $proposal);

				if($proposal->createdBy && $proposal->createdBy->isSubContractorPrime() && $proposal->createdBy->dataMaskingEnabled()) {
					DataMasking::enable();
					$this->worksheetsService->createPDF($worksheet, $proposal, false, true, $subWorksheetOldPath);

					DataMasking::disable();
				}
			}
        }
        $thankYouEmail = isset($input['thank_you_email']) ? $input['thank_you_email'] : true;
        if (ine($input, 'status')) {
            Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ShareProposalStatus', new ShareProposalStatus($proposal, $thankYouEmail));
        }

        if($proposal->isPDF() && $proposal->isAccepted() && $proposal->signature) {
			$data['proposal_id'] = $proposal->id;

			JobQueue::enqueue(JobQueue::PROPOSAL_DIGITAL_SIGN, $proposal->company_id, $proposal->id, $data);
		}

		return $proposal;
    }

    /**
     * Rotate image
     * @param  proposal             Proposal
     * @param  integer $rotateAngle Rotation Angle
     * @return Proposal
     */
    public function rotateImage($proposal, $rotateAngle = 0)
    {
        // get file paths..
        $oldFilePath = $proposal->file_path;
        $oldThumbPath = $proposal->thumb;

        //rotate image
        $extension = File::extension($proposal->file_path);
        $physicallyName = uniqueTimestamp() . '.' . $extension;
        $newFilePath = 'proposals/' . $physicallyName;
        $this->rotate($proposal->file_path, $newFilePath, $rotateAngle);

        //rotate thumb
        $newThumbPath = 'proposals/thumb/' . $physicallyName;
        $this->rotate($proposal->thumb, $newThumbPath, $rotateAngle);

        //update proposal with file path
        $proposal->update([
            'file_path' => $newFilePath,
            'thumb' => $newThumbPath
        ]);

        // delete old files
        if (!empty($oldFilePath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldFilePath);
        }
        if (!empty($oldThumbPath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $oldThumbPath);
        }

        return $proposal;
    }

    /**
     * Get proposal page by page id
     * @param  Int $id Page Id
     * @return Page
     */
    public function getPageByPageId($id)
    {
        $page = ProposalPage::company(getScopeId())->findOrFail($id);

        return $page;
    }

    /**
     * Update Template Value
     * @param  $proposal     Proposal Data
     * @param  $dataElements New Values of Template
     * @return Updated Template
     */
    public function updateTemplateValue($proposal, $dataElements)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
		}
        DB::beginTransaction();
        try {
            $pageAutoFill = new ProposalPageAutoFillDbElement;

            $worksheet = $proposal->worksheet;

			if($worksheet) {
				$templatePages = $worksheet->templatePages;

				foreach ($templatePages as $key => $templatePage) {
					$updatedTemplate = $pageAutoFill->fillTemplateValue($templatePage->content, $dataElements);
					$templatePage->update([
						'content' => $updatedTemplate,
					]);
				}

				$worksheet = $this->worksheetsService->createPDF($worksheet, $proposal);

				$proposal = Proposal::find($proposal->id);

			}else {
				foreach ($proposal->pages as $page) {
					$updatedTemplate = $pageAutoFill->fillTemplateValue($page->template, $dataElements);
					$page->update([
						'template' => $updatedTemplate,
					]);
				}

				// Update thumb and Pdf after updating template value..
				$proposal = $this->updateThumbAndPdf($proposal, $page, $proposal->page_type);
			}
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        return $proposal;
    }

    /**
     * Update Thumb And Pdf After Updating Template Value
     * @param  Proposal $proposal Proposal Data
     * @param  ProposalPage $page Page Data
     * @param               $pageType Type Of Page
     * @return Proposal Data
     */
    public function updateThumbAndPdf(Proposal $proposal, ProposalPage $page, $pageType)
    {
        if($proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans('response.error.proposal_cannot_update'));
		}
        // Update Proposal PDF...
        $existingFile = null;
        if (!empty($proposal->file_path)) {
            $existingFile = config('jp.BASE_PATH') . $proposal->file_path;
        }

        $proposal = Proposal::with('pages', 'attachments')->find($proposal->id);
        $filename = $proposal->id . '_' . Carbon::now()->timestamp . rand() . '.pdf';
        $baseName = 'proposals/' . $filename;
        $fullPath = config('jp.BASE_PATH') . $baseName;

        $pageHeight = '23.9cm';

        if ($proposal->page_type == 'legal-page') {
            $pageHeight = '28.6cm';
        }

        $job = $proposal->job;
        $customer = $job->customer;

        $pdf = PDF::loadView('proposal.multipages', [
            'pages' => $proposal->pages,
            'pageType' => $proposal->page_type,
            'attachments' => $proposal->attachments,
            'attachments_per_page' => $proposal->attachments_per_page,
            'company' => $proposal->company,
            'job' => $job,
            'customer' => $customer,
            'proposal' => $proposal,
        ])
            ->setOption('page-size', 'A4')
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-top', '0.5cm')
            ->setOption('margin-bottom', '0.5cm')
            ->setOption('page-width', '16.8cm')
            ->setOption('page-height', $pageHeight);

        $mimeType = 'application/pdf';
        FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

        $proposal->file_name = $proposal->title . '.pdf';
        $proposal->file_path = $baseName;
        $proposal->file_mime_type = $mimeType;
        $proposal->file_size = FlySystem::getSize($fullPath);
        $proposal->save();

        // delete existing Pdf
        if (!is_null($existingFile)) {
            FlySystem::delete($existingFile);
        }

        // Update thumb and image of proposal...
        $contents = \view('proposal.proposal')
            ->with('page', $page)
            ->with('pageType', $pageType)
            ->render();
        $filename = Carbon::now()->timestamp . rand() . '.jpg';
        $imageBaseName = 'proposals/' . $filename;
        $thumbBaseName = 'proposals/thumb/' . $filename;
        $imageFullPath = config('jp.BASE_PATH') . $imageBaseName;
        $thumbFullPath = config('jp.BASE_PATH') . $thumbBaseName;

        $snappy = App::make('snappy.image');
        $snappy->setOption('width', '794');
        if ($pageType == 'legal-page') {
            $snappy->setOption('height', '1344');
        } else {
            $snappy->setOption('height', '1122');
        }
        $image = $snappy->getOutputFromHtml($contents);

        // delete old image if exist...
        if (!empty($page->image)) {
            $filePath = config('jp.BASE_PATH') . $page->image;
            FlySystem::delete($filePath);
        }

        // save image...
        FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);

        // resize for thumb..
        $image = \Image::make($image);
        if ($image->height() > $image->width()) {
            $image->heighten(250, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(250, function ($constraint) {
                $constraint->upsize();
            });
        }

        //delete old thumb if exist...
        if (!empty($page->thumb)) {
            $filePath = config('jp.BASE_PATH') . $page->thumb;
            FlySystem::delete($filePath);
        }

        // save thumb ..
        FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

        $page->image = $imageBaseName;
        $page->thumb = $thumbBaseName;
        $page->save();

        return $proposal;
    }

    /**
	 * add digital signature queue request for a proposal
	 * @param  Object | $proposal | Proposal model object
	 * @return true
	 */
	public function authorizeDigitally($proposal)
	{
		if($proposal->isDigitalSigned()) {
			throw new ProposalAlreadySignedDigitally(trans('response.error.proposal_digitally_sign'));
		}

		if(!$proposal->isAccepted()) {
			throw new ProposalStatusMustBeAccepted(trans('response.error.proposal_status_must_be_accepted'));
		}

		if(!$proposal->signature) {
			throw new ProposalSignatureNotExist(trans('response.error.proposal_signature_not_exist'));
		}

		if(!$proposal->hasDigitalAuthorizationQueue()) {
			throw new ProposalCannotBeUpdate(trans("response.error.proposal_queue_not_found"));
		}

		if(!$proposal->isPDF()) {
			throw new ProposalCannotBeUpdate("Proposal file must be type of PDF.");
		}

		$queueStatus = $proposal->digitalSignQueueStatus;

		switch ($queueStatus->status) {
			case JobQueue::STATUS_QUEUED:
			case JobQueue::STATUS_IN_PROCESS:
				throw new JobAlreadyInQueueException("Propsoal digital authorization request is in mid of process.");
				break;
			case JobQueue::STATUS_COMPLETED:
				throw new JobAlreadyInQueueException("Propsoal digital authorization request is already completed.");
				break;
			default:
				break;
		}

		$data['proposal_id'] = $proposal->id;

		JobQueue::enqueue(JobQueue::PROPOSAL_DIGITAL_SIGN, $proposal->company_id, $proposal->id, $data);

		return true;
	}

    /******************** Private Section ********************/

    /**
     * Create Thumb
     * @param  ProposalPage $page | Proposal object
     * @return [type]             [description]
     */
    private function createThumb(ProposalPage $page, $pageType, $proposal = null)
    {
        $contents = \View::make('proposal.proposal', [
			'proposal' => $proposal,
			'page' => $page,
			'pageType' => $pageType,
		])->render();
        $filename = Carbon::now()->timestamp . rand() . '.jpg';
        $imageBaseName = 'proposals/' . $filename;
        $thumbBaseName = 'proposals/thumb/' . $filename;
        $imageFullPath = config('jp.BASE_PATH') . $imageBaseName;
        $thumbFullPath = config('jp.BASE_PATH') . $thumbBaseName;

        $snappy = App::make('snappy.image');
        $snappy->setOption('width', '794');
        if ($pageType == 'legal-page') {
            $snappy->setOption('height', '1344');
        } else {
            $snappy->setOption('height', '1122');
        }

        $image = $snappy->getOutputFromHtml($contents);

        // save image...
        FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);

        // resize for thumb..
        $image = \Image::make($image);
        if ($image->height() > $image->width()) {
            $image->heighten(250, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(250, function ($constraint) {
                $constraint->upsize();
            });
        }
        // save thumb ..
        FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

        $page->image = $imageBaseName;
        $page->thumb = $thumbBaseName;
        $page->save();
        return $page;
    }

    private function attachments($proposal, $data)
    {
        if (!ine($data, 'attachments')) {
            return;
        }
        try {
            foreach ((array)$data['attachments'] as $attachment) {
                if (ine($attachment, 'type') && ine($attachment, 'value')) {
                    if ($attachment['type'] == 'resource') {
                        $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
                        $file = $resourcesRepo->getFile($attachment['value']);
                        $filePath = config('resources.BASE_PATH') . $file->path;
                        $name = $file->name;
                        $mimeType = $file->mime_type;
                        $size = $file->size;
                    } elseif ($attachment['type'] == 'proposal' || $attachment['type'] == 'estimate') {
                        if ($attachment['type'] == 'proposal') {
                            $file = $this->repo->getById($attachment['value']);
                        } else {
                            $estimateRepo = App::make(\App\Repositories\EstimationsRepository::class);
                            $file = $estimateRepo->getById($attachment['value']);
                        }

                        $filePath = config('jp.BASE_PATH') . $file->file_path;
                        $name = $file->title;
                        $mimeType = $file->file_mime_type;
                        $size = $file->file_size;
                    } else {
                        continue;
                    }
                    // get file extension..
                    $extension = File::extension($filePath);

                    $destinationPath = config('jp.BASE_PATH');
                    // create physical file name..
                    $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;
                    $basePath = 'proposals/attachments/' . $physicalName;
                    // copy file to attachment directory..
                    if (FlySystem::copy($filePath, $destinationPath . $basePath)) {
                        ProposalAttachment::create([
                            'proposal_id' => $proposal->id,
                            'name' => $name,
                            'path' => $basePath,
                            'size' => $size,
                            'mime_type' => $mimeType,
                        ]);
                    }
                }
            }
        } catch (ModelNotFoundException $e) {
            if ($e->getMessage() == "No query results for model [Resource].") {
                throw new InvalidAttachmentException("Invalid Attachment (s).");
            }
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Copy attachments
     * @param  Instance $destProposal Destincation proposal
     * @param  Instance $proposal proposal
     * @return void
     */
    private function copyAttachments($destProposal, $proposal, $meta = [])
    {
        $attachments = [];

        $attachments = $destProposal->attachments();

        if (ine($meta, 'delete_attachments')) {
            $attachments->whereNotIn('id', arry_fu($meta['delete_attachments']));
        }

        $attachments = $attachments->get();

        if ($attachments->isEmpty()) {
            return false;
        }

        foreach ($attachments as $attachment) {
            $filePath = config('jp.BASE_PATH') . $attachment->path;
            // get file extension..
            $extension = File::extension($filePath);

            $destinationPath = config('jp.BASE_PATH');
            // create physical file name..
            $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;
            $basePath = 'proposals/attachments/' . $physicalName;
            // copy file to attachment directory..
            if (FlySystem::copy($filePath, $destinationPath . $basePath)) {
                ProposalAttachment::create([
                    'proposal_id' => $proposal->id,
                    'name' => $attachment->name,
                    'path' => $basePath,
                    'size' => $attachment->size,
                    'mime_type' => $attachment->mime_type,
                ]);
            }
        }
    }

    /**
     * Copy pages
     * @param  instance $destProposal Destincation proposal
     * @param  instance $proposal Proposal
     * @return void
     */
    private function copyPages($destProposal, $proposal)
    {
        if (!($pages = $destProposal->pages)) {
            return false;
        }
        $pagesArray = [];

        foreach ($pages as $page) {
            $page['tables'] = $page->pageTableCalculations;

            $this->createPage(
                $proposal,
                $page->template,
                $page->order,
                $page->toArray()
            );
        }
    }

    /**
     * Rotate Image
     * @param  String $oldFilePath Old file path
     * @param  String $newFilePath New file path
     * @param  integer $rotateAngle Rotation Angle
     * @return Response
     */
    private function rotate($oldFilePath, $newFilePath, $rotateAngle = 0)
    {
        $filePath = config('jp.BASE_PATH') . $oldFilePath;
        $basePath = config('jp.BASE_PATH') . $newFilePath;
        $img = \Image::make(\FlySystem::read($filePath));
        $img->rotate($rotateAngle);
        FlySystem::put($basePath, $img->encode()->getEncoded());
    }

    private function uploadBase64($data, $jobId, $meta = array())
    {
        $name = uniqueTimestamp(). '.jpg';
        $basePath   = 'proposals/';
        $fullPath   = config('jp.BASE_PATH').$basePath;
        $thumbName = 'proposals/thumb/'. $name;
        $fullThumbPath = config('jp.BASE_PATH'). $thumbName;
        $rotationAngle = ine($meta, 'rotation_angle') ? $meta['rotation_angle'] : null;
        $file = uploadBase64Image($data, $fullPath, $name, $rotationAngle, true, null, $fullThumbPath);
        if(!$file) {
            throw new InvalidFileException("Invalid File Type");
        }
        $meta['file_name']      = $file['name'];
        $meta['file_size']      = $file['size'];
        $meta['file_mime_type'] = $file['mime_type'];
        $meta['file_path']      = $basePath . '/' . $name;
        $meta['is_file']        = true;
        $meta['thumb']          = $thumbName;
        $createdBy              = \Auth::id();
        $proposal = $this->repo->saveProposal($jobId, $createdBy, $meta);

        $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
		$eventData = [
			'reference_id' => $proposal->id,
			'name' => $proposal->id,
			'job_id' => $proposal->job_id,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobProposalStoreFile($eventData));
        //proposal created event..
        Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated', new ProposalCreated($proposal));
        return $proposal;
    }
}
