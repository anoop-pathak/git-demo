<?php

namespace App\Http\Controllers;

use Request;
use FlySystem;
use Carbon\Carbon;
use App\Models\Job;
use App\Models\Proposal;
use App\Models\Template;
use App\Models\ApiResponse;
use App\Models\ProposalPage;
use App\Services\ProposalService;
use App\Models\ProposalAttachment;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\App;
use App\Exceptions\InvalidFileException;
use App\Repositories\ProposalsRepository;
use Illuminate\Support\Facades\Validator;
use App\Transformers\ProposalsTransformer;
use App\Services\Google\GoogleSheetService;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Transformers\ProposalPageTransformer;
use App\Exceptions\InvalidAttachmentException;
use App\Exceptions\GoogleAccountNotConnectedException;
use App\Services\SecurityCheck;
use App\Events\Folders\JobProposalDeleteFile;
use App\Events\Folders\JobProposalRestoreFile;
use App\Services\Folders\FolderService;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use App\Exceptions\Proposal\ProposalCannotBeUpdate;
use App\Exceptions\Proposal\ProposalAlreadySignedDigitally;
use App\Exceptions\Proposal\ProposalSignatureNotExist;
use App\Exceptions\Proposal\ProposalStatusMustBeAccepted;
use App\Exceptions\Queue\JobAlreadyInQueueException;
use Exception;
use Event;

class ProposalsController extends ApiController
{

    /**
     * Representatives Repo
     * @var \App\Repositories\ProposalsRepository
     */

    protected $repo;
    protected $service;

    public function __construct(ProposalsRepository $repo, Larasponse $response, ProposalService $service, FolderService $folderService, JobRepository $jobRepo)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->service = $service;
        $this->jobRepo = $jobRepo;
        $this->folderService = $folderService;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    public function index()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required_without:deleted_proposals']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $proposals = $this->repo->get($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($proposals, new ProposalsTransformer));
        }

        return ApiResponse::success($this->response->paginatedCollection($proposals, new ProposalsTransformer));
    }

    public function store()
    {
        set_time_limit(0);
        $input = Request::all();

        $validator = Validator::make($input, Proposal::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::find($input['job_id']);
        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'job id']));
        }

        if (ine($input, 'serial_number')
            && $this->repo->isExistSerialNumber($input['serial_number'])
            && !ine($input, 'save_as')) {
            $data['serial_number'] = $this->repo->getSerialNumber();

            return ApiResponse::errorGeneral(trans('response.error.serial_number_already_exist', ['attribute' => 'Proposal']), [], $data);
        }

        // handle single page
        if (ine($input, 'template')) {
            $input['pages'][0]['template'] = $input['template'];
            $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
        }
        try {
            $createdBy = Auth::id();
            $proposal = $this->service->create(
                $input['job_id'],
                $input['pages'],
                $createdBy,
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Proposal']),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch (InvalidAttachmentException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function createGoogleSheet()
    {
        $input = Request::all();

        $validator = Validator::make($input, Proposal::getCreateGoogleSheetRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $proposal = $this->service->createGoogleSheet(
                $input['job_id'],
                $input
            );
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Proposal']),
            'data' => $this->response->item($proposal, new ProposalsTransformer)
        ]);
    }

    public function createGoogleSheetForRoofingAndMore()
    {
        $input = Request::all();

        $validator = Validator::make($input, [
            'job_id' => 'required',
            'template_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!in_array((int)$input['template_id'], config('jp.proposal_template_ids'))) {
            return null;
        }

        try {
            $googleSheetService = App::make(GoogleSheetService::class);

            $template = null;
            $googleSheetTemplates = config('jp.google_sheet_proposal_templates');

            if (!ine($input, 'google_sheet_template')) {
                $template = Template::find($googleSheetTemplates['sheet1']);
            } else {
                if (isset($googleSheetTemplates[$input['google_sheet_template']])) {
                    $sheetTemplateId = $googleSheetTemplates[$input['google_sheet_template']];
                    $template = Template::find($sheetTemplateId);
                }
            }

            if (!$template) {
                return ApiResponse::errorGeneral('Google Sheet template not found.');
            }

            if (!$googleSheetTemplateId = $template->google_sheet_id) {
                return ApiResponse::errorGeneral('Google Sheet template not found.');
            }

            $sheetId = $googleSheetService->createFromExistingSheet(
                $googleSheetTemplateId,
                $name = timestamp() . '_' . $template->title
            );

            return ApiResponse::success([

                'url' => getGoogleSheetUrl($sheetId),
            ]);
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Proposal']),
            'data' => $this->response->item($proposal, new ProposalsTransformer)
        ]);
    }

    public function show($id)
    {
        $input = Request::all();
        $scope = App::make(\App\Services\Contexts\Context::class);
        if (config('is_mobile')
            && in_array($scope->id(), config('disable_proposal_for_company'))) {
            return ApiResponse::errorInternal(trans('response.error.proposal_tool_soon_available'));
        }

        $proposal = $this->repo->getById($id, $with = [], $input);

        return ApiResponse::success([
            'data' => $this->response->item($proposal, new ProposalsTransformer)
        ]);
    }

    public function update($id)
    {
        set_time_limit(0);
        $proposal = $this->repo->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $input = Request::all();

        $validator = Validator::make($input, Proposal::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        // handle single page
        if (ine($input, 'template')) {
            $input['pages'][0]['template'] = $input['template'];
            $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
        }
        $input['is_file'] = 0;
        try {
			$this->service->update($proposal, $input);

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Proposal']),
				'data' => $this->response->item($proposal, new ProposalsTransformer)
			]);
		} catch(ProposalCannotBeUpdate $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal();
		}
    }

    public function delete_page($pageId)
    {
        $page = ProposalPage::findOrFail($pageId);
        try {
            $this->service->deletePage($page);
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Page'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function restore($id)
	{
		if(Auth::user()->iAuthority()) {

			return ApiResponse::errorForbidden();
		}

		if(!SecurityCheck::verifyPassword()) {

			return SecurityCheck::$error;
		}

		$proposal = $this->repo->getDeletedById($id);
		try {
            $proposal->restore();
            Event::fire('JobProgress.Templates.Events.Folder.restoreFile', new JobProposalRestoreFile($id));
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::success([
			'message' => trans('response.success.restored', ['attribute' => 'Proposal'])
		]);
	}

    public function destroy($id)
    {
        $proposal = $this->repo->getById($id);

        if ($proposal->delete()) {
            Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new JobProposalDeleteFile($id));
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Proposal'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    public function download($id)
    {
        try {
            $proposal = $this->repo->getById($id);

            if ($proposal->type == Proposal::GOOGLE_SHEET) {
                $googleSheetService = App::make(\App\Google\GoogleSheetService::class);

                return $googleSheetService->downloadFile($proposal->google_sheet_id);
            }

            $fullPath = config('jp.BASE_PATH') . $proposal->file_path;

            return FlySystem::download($fullPath, $proposal->file_name);

            // $fileResource = FlySystem::read($fullPath);
            // $response = \response($fileResource, 200);
            // $response->header('Content-Type', $proposal->file_mime_type);
            // $response->header('Content-Disposition' ,'filename="'.$proposal->file_name.'"');
            // return $response;
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function file_upload()
    {
        $input = Request::onlyLegacy('job_id','file', 'make_pdf','image_base_64', 'rotation_angle', 'title', 'parent_id');
        $validator = Validator::make($input, Proposal::getFileUploadRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::find($input['job_id']);
        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'job id']));
        }

        if(!$input['image_base_64'] && !(Request::hasFile('file'))){
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'file']));
        }

        try {
            $proposal = $this->service->uploadFile(
                $input['job_id'],
                $input['file'],
                $input['image_base_64'],
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function upload_multiple_file()
    {

        $input = Request::onlyLegacy('job_id', 'files', 'make_pdf', 'parent_id');
        $validator = Validator::make($input, Proposal::getUploadMultipleFilesRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = Job::find($input['job_id']);
        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'job id']));
        }
        try {
            foreach ($input['files'] as $file) {
                $this->service->uploadFile($input['job_id'], $file, $input);
            }

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded')
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_file($id)
    {
        $input = Request::onlyLegacy('base64_encoded', 'download');

        $proposal = $this->repo->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            $googleSheetService = App::make(\App\Google\GoogleSheetService::class);

            return $googleSheetService->downloadFile($proposal->google_sheet_id);
        }

        if (empty($proposal->file_path)) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'File']));
        }

        $fullPath = config('jp.BASE_PATH') . $proposal->file_path;
        if ((bool)$input['base64_encoded']) {
            return $this->getBase64EncodedImage($fullPath);
        }

        if (!$input['download']) {
            $fileResource = FlySystem::read($fullPath);
            $response = \response($fileResource, 200);
            $response->header('Content-Disposition', 'filename="' . $proposal->file_name . '"');
            $response->header('Content-Type', $proposal->file_mime_type);
            return $response;
        } else {
            return FlySystem::download($fullPath, $proposal->file_name);

            // $response->header('Content-Disposition' ,'attachment; filename="'.$proposal->file_name.'"');
        }
    }

    public function rename($id)
    {
        $proposal = $this->repo->getById($id);
        $input = Request::onlyLegacy('title');
        $validator = Validator::make($input, ['title' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $this->service->rename($proposal, $input['title']);
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.rename', ['attribute' => 'Proposal'])
        ]);
    }

    public function edit_image_file($id)
    {
        $proposal = $this->repo->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $input = Request::onlyLegacy('base64_string', 'rotation_angle');
        $validator = Validator::make($input, ['base64_string' => 'required|string']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $proposal = $this->editImageFile($proposal, $input['base64_string'], $input);
            return ApiResponse::success($this->response->item($proposal, new ProposalsTransformer));
        } catch (InvalidFileException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * [removeAttachment description]
     * @param  [type] $attachmentId [description]
     * @return [type]               [description]
     */
    public function removeAttachment($attachmentId)
    {
        $attachment = ProposalAttachment::findOrFail($attachmentId);
        try {
            if (!empty($attachment->path)) {
                $filePath = config('jp.BASE_PATH') . $attachment->path;
                FlySystem::delete($filePath);
            }

            $attachment->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Attachment']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * get seriel number of proposal
     * @return counts:int
     */
    public function getSerialNumber()
    {
        $data['serial_number'] = $this->repo->getSerialNumber();

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * add or update note in proposals
     * PUT /proposals/{id}/update_note
     *
     * @return Response
     */
    public function updateNote($id)
    {
        $input = Request::onlyLegacy('note');

        //find proposal
        $proposal = $this->repo->getById($id);

        try {
            $oldNote = $proposal->note;
            $proposal->note = $input['note'];
            $proposal->save();
            // update job work crew notes..
            if (!empty($input['note'])
                && ($proposal->note !== $oldNote)
                && ($proposal->status == Proposal::ACCEPTED)
            ) {
                $job = $proposal->job;
                $note = "Proposal - $proposal->title";
                $note .= ' \n ' . $proposal->note;
                $job->work_crew_notes .= ' \n ' . $note;
                $job->save();
            }

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Proposal note'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Generate Proposal Pdf
     * Post proposals/generate_pdf
     * @return response
     */
    public function generatePdf()
    {
        $input = Request::onlyLegacy('pages', 'template', 'page_type');
        $validator = Validator::make($input, Proposal::getPdfRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // handle single page
        if (ine($input, 'template')) {
            $input['pages'][0]['template'] = $input['template'];
            $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
        }

        if (!ine($input, 'page_type')) {
            $input['page_type'] = 'a4-page';
        }

        try {
            $pdfToken = $this->service->generatePdf($input['page_type'], $input['pages']);

            return ApiResponse::success([
                'token' => $pdfToken
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get pdf print
     * Get proposals/get_pdf/{token}
     * @param  [int] $token timestamp
     * @return PDF
     */
    public function pdfPrint($token)
    {
        try {
            $fileName = 'temp/' . $token . '.pdf';
            $path = public_path('uploads/' . $fileName);
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'filename="invoice.pdf"'
            ];

            return \response(file_get_contents($path), 200, $headers);
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return \view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.error_page'),
            ]);
        }
    }

    /**
     * Proposal Copy
     * Get proposals/copy
     * @return json response
     */
    public function copyProposal()
    {
        $input = Request::onlyLegacy('proposal_id', 'title', 'parent_id');
        $validator = Validator::make($input, ['proposal_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $proposal = $this->repo->getById($input['proposal_id']);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        try {
            $proposal = $this->service->makeCopy($proposal,Auth::id(), $input);

            return ApiResponse::success([
                'message' => trans('response.success.copied', ['attribute' => 'Proposal']),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Proposal Status update
     * @param  Int $proposalId Proposal Id
     * @return Response
     */
    public function updateStatus($proposalId)
    {

        if(Auth::user()->isSubContractorPrime()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::onlyLegacy('status', 'thank_you_email');
        $validator = Validator::make($input, Proposal::getStatusUpdateRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $proposal = $this->repo->getById($proposalId);
        try {
            if ($proposal->status == $input['status']) {
                goto sendResponse;
            }
            $thankYouEmail = isset($input['thank_you_email']) ? $input['thank_you_email'] : true;

            $this->service->updateStatus($proposal, $input['status'], $thankYouEmail);
            sendResponse:

            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Proposal status']),
            ]);
        } catch(ProposalCannotBeUpdate $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Proposal Share on home owner page
     * @param  int $id Proposal Id
     * @return Response
     */
    public function shareOnHomeOwnerPage($id)
    {
        $input = Request::onlyLegacy('share');
        $proposal = $this->repo->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $proposal->share_on_hop = ($input['share']);
        $proposal->share_on_hop_at = ($input['share']) ? Carbon::now() : null;
        $proposal->update();

        $msg = 'Shared on Customer Web Page.';
        if (!$proposal->share_on_hop) {
            $msg = 'Removed from Customer Web Page.';
        }

        return ApiResponse::success([
            'message' => trans($msg)
        ]);
    }

    /**
     * Sign Proposal
     * @param  Int $id Proposal Id
     * @return Response
     */
    public function signProposal($id)
    {
        $proposal = $this->service->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $input = Request::all();

        $validator = Validator::make($input, ['signature' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['job_id'] = $proposal->job_id;

        if (!$proposal->is_file) {
            $validator = Validator::make($input, Proposal::getRules());
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
        }

        DB::beginTransaction();
        try {
            $input['status'] = Proposal::ACCEPTED;
            $this->service->updateSharedProposal($proposal, $input);
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        return ApiResponse::success(['message' => 'Proposal accepted.']);
    }

    /**
     * Get Share Email Content
     * @param  int $id Proposal Id
     * @return response
     */
    public function getEmailContent($id)
    {
        $proposal = $this->service->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        try {
            if (!$proposal->token) {
                $token = generateUniqueToken();
                $proposal->token = $token;
                $proposal->save();
            }

            $job = $proposal->job;
            $data['subject'] = $job->company->name . ' - New Document';
            $data['content'] = $this->service->getEmailContent($proposal);
            $data['share_url'] = config('app.url') . config('jp.BASE_PROPOSAL_PATH') . $proposal->token . '/view';

            return ApiResponse::success(['data' => $data]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * send email containing proposal link to customer to view proposal
     * @return Response
     */
    public function shareProposal($id)
    {

        $proposal = $this->service->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $job = $proposal->job;
        $customer = $job->customer;
        /* set customer (receiver) email */
        $email = $customer->email;
        if (!$email) {
            return ApiResponse::errorGeneral(trans('response.error.set_customer_email'));
        }
        try {
            if (!$proposal->token) {
                $token = generateUniqueToken();
                $proposal->token = $token;
            }
            /* set email subject */
            $subject = '[' . $job->company->name . '] New Proposal';

            /* get email content */
            $content = $this->service->getEmailContent($proposal);
            /* set meta */
            $meta['customer_id'] = $customer->id;
            $meta['job_id'] = $job->id;

            App::make(\App\Services\Emails\EmailServices::class)->sendEmail(
                $subject,
                $content,
                (array)$email,
                [],
                [],
                [],
        Auth::id(),
                $meta
            );

            $proposal->status = Proposal::SENT;
            $proposal->save();

            return ApiResponse::success(['message' => trans('response.success.email_sent')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Get Share Url
     * @param  int $id | Proposal Id
     * @return response
     */
    public function getShareUrl($id)
    {

        $proposal = $this->service->getById($id);

        if ($proposal->type == Proposal::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        if (!$proposal->token) {
            $token = generateUniqueToken();
            $proposal->token = $token;
            $proposal->save();
        }

        $data['share_url'] = config('app.url') . config('jp.BASE_PROPOSAL_PATH') . $proposal->token . '/view';

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * Rotate image file
     * Post proposals/{id}/rotate_image
     * @param  int $id Proposal Id
     * @return Response
     */
    public function rotate_image_file($id)
    {
        $input = Request::onlyLegacy('rotation_angle');
        $validator = Validator::make($input, ['rotation_angle' => 'numeric']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($input['rotation_angle'] == '') {
            return ApiResponse::errorGeneral(trans('response.error.roation_angle_required'));
        }

        $proposal = $this->repo->getById($id);
        if (!in_array($proposal->file_mime_type, config('resources.image_types'))) {
            return ApiResponse::errorGeneral(trans('response.error.only_image_rotate'));
        }

        try {
            $proposal = $this->service->rotateImage($proposal, $input['rotation_angle']);

            return ApiResponse::success([
                'message' => trans('response.success.rotated', ['attribute' => 'Image']),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Create proposal by pages
     * Post proposals/create_proposal_by_pages
     * @return Response
     */
    public function createProposalByPages()
    {
        set_time_limit(0);
        $input = Request::all();

        $validator = Validator::make($input, Proposal::getProposalPageRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $job = $this->jobRepo->findById($input['job_id']);

        if (ine($input, 'serial_number')
            && $this->repo->isExistSerialNumber($input['serial_number'])
            && !ine($input, 'save_as')) {
            $data['serial_number'] = $this->repo->getSerialNumber();

            return ApiResponse::errorGeneral(trans('response.error.serial_number_already_exist', ['attribute' => 'Proposal']), [], $data);
        }
        DB::beginTransaction();
        try {
            $proposal = $this->service->saveProposalByPages($job, $input);
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Proposal']),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch(FolderNotExistException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update proposal by pages
     * Put proposals/{id}/update_proposal_by_pages
     * @param  Int $id Proposal Id
     * @return Response
     */
    public function updateProposalByPages($id)
    {
        set_time_limit(0);
        $input = Request::all();

        $validator = Validator::make($input, Proposal::getProposalPageUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $proposal = $this->repo->getById($id);
        DB::beginTransaction();

        try {
            $proposal = $this->service->updateProposalByPages($proposal, $input);
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Proposal']),
                'data' => $this->response->item($proposal, new ProposalsTransformer)
            ]);
        } catch(ProposalCannotBeUpdate $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get proposal page ids
     * @param  Int $id Page Id
     * @return Response
     */
    public function getPageIds($id)
    {
        $proposal = $this->repo->getById($id);
        $pageIds = $proposal->pages()->pluck('id')->toArray();

        return ApiResponse::success(['data' => $pageIds]);
    }

    /**
     * Get single page detail
     * @param  Int $id Page Id
     * @return Response
     */
    public function getSinglePage($id)
    {
        $page = $this->service->getPageByPageId($id);

        return ApiResponse::success([
            'data' => $this->response->item($page, new ProposalPageTransformer)
        ]);
    }

    /**
	 * Create folder in proposals.
	 *
	 * POST - /proposals/folder
	 * @return json response.
	 */
	public function createFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Proposal::getFolderRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->getById($inputs['job_id']);

		try {
			$item = $this->folderService->createProposalFolder($inputs);

			return ApiResponse::success([
				'data' => $this->response->item($item, new ProposalsTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * add digital signature request queue for a proposal
	 *
	 * PUT - proposals/{id}/authorize_digitally
	 *
	 * @param  Int | $id | Id of a proposal
	 * @return Response
	 */
	public function authorizeDigitally($id)
	{
		$proposal = $this->repo->getById($id);

		DB::beginTransaction();

		try {
			$this->service->authorizeDigitally($proposal);

			DB::commit();

			return ApiResponse::success(['message' => trans('response.success.proposal_digitally_sign_request_queued')]);
		} catch(JobAlreadyInQueueException $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ProposalCannotBeUpdate $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ProposalAlreadySignedDigitally $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ProposalSignatureNotExist $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ProposalStatusMustBeAccepted $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}


    /************************ Private function **********************/

    /**
     * This is to edit only image files
     */
    private function editImageFile(Proposal $proposal, $base64_string, $meta = [])
    {
        $previousImagePath = $proposal->file_path;
        $previousThumbPath = $proposal->thumb;


        $fullPath = config('jp.BASE_PATH') . 'proposals';
        $physicalName = timestamp() . "_{$proposal->id}_image.jpg";


        //for image rotation
        $rotationAngle = null;
        if (ine($meta, 'rotation_angle')) {
            $rotationAngle = $meta['rotation_angle'];
        }

        $uploadedFile = uploadBase64Image($base64_string, $fullPath, $physicalName, $rotationAngle);

        if (!$uploadedFile) {
            throw new InvalidFileException(trans('response.error.invalid', ['attribute' => 'File Type']));
        }

        // create thumb..
        $imagePath = "{$fullPath}/{$physicalName}";
        $thumbPath = "{$fullPath}/thumb/$physicalName";
        $thumb = \Image::make(\FlySystem::read($imagePath));
        if ($thumb->height() > $thumb->width()) {
            $thumb->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $thumb->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }
        // save thumb..
        FlySystem::put($thumbPath, $thumb->encode()->getEncoded());

        // update database..
        $proposal->file_size = $uploadedFile['size'];
        $proposal->file_path = 'proposals/' . $uploadedFile['name'];
        $proposal->thumb = 'proposals/thumb/' . $uploadedFile['name'];
        $proposal->file_mime_type = $uploadedFile['mime_type'];
        $proposal->save();

        //delete old files
        if (!empty($previousImagePath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $previousImagePath);
        }

        if (!empty($previousThumbPath)) {
            FlySystem::delete(config('jp.BASE_PATH') . $previousThumbPath);
        }

        return $proposal;
    }

    private function getBase64EncodedImage($filePath)
    {
        try {
            $data = getBase64EncodedData($filePath);
            return ApiResponse::success(['data' => $data]);
        } catch (InvalidFileException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
