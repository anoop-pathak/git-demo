<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Event;
use Request;
use FlySystem;
use App\Models\Proposal;
use App\Models\ApiResponse;
use App\Models\ProposalViewer;
use App\Services\ProposalService;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Events\ShareProposalStatus;
use Illuminate\Support\Facades\Validator;
use App\Transformers\ProposalsTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\Proposal\ProposalCannotBeUpdate;

class ShareProposalController extends ApiController
{
    protected $model;
    protected $service;
    protected $response;

    public function __construct(Proposal $model, ProposalService $service, Larasponse $response)
    {
        $this->model = $model;
        $this->service = $service;
        $this->response = $response;

        if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
    }

    /**
     * view proposal to customer
     *
     * @return Response
     */
    public function viewProposal($token)
    {
        try {
            $proposal = $this->model->whereToken($token)
                ->with('job', 'company', 'job.customer')
                ->firstOrFail();

            if (!$proposal->job) {
                return view('error-page', [
                    'errorDetail' => 'Job Not Found',
                    'message' => trans('response.error.proposal_error_page')
                ]);
            }

            if ($proposal->status == Proposal::SENT) {
                $proposal->status = Proposal::VIEWED;
                $proposal->save();
                setScopeId($proposal->company_id);
                Event::fire('JobProgress.Workflow.Steps.Proposal.Events.ShareProposalStatus', new ShareProposalStatus($proposal));
            }

            $proposalViewers = ProposalViewer::where('company_id', $proposal->company_id)
            ->active()
            ->orderBy('display_order', 'ASC')
            ->get();

            $proposalViewersId = Request::get('id');
            $getProposalViewer = ProposalViewer::where('company_id', $proposal->company_id)->find($proposalViewersId);


            $mimeTypes = array_merge(config('resources.image_types'), config('resources.pdf_types'));

            /* get download thumb to show download link*/
            if (!in_array($proposal->file_mime_type, $mimeTypes)) {
                $downloadThumb = getFileIcon($proposal->file_mime_type, $proposal->file_path);
                // $downloadThumb = $this->getDownloadThumb($proposal->file_mime_type);
            }

            // Set S3 cookies
            \App\Helpers\CloudFrontSignedCookieHelper::setCookies();

            return view('proposal.public', [
                'proposal'          => $proposal,
                'proposal_viewers'  => $proposalViewers,
                'get_Proposal_Viewer' => $getProposalViewer,
                'download_thumb'    => isset($downloadThumb) ? $downloadThumb : '',
            ]);
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.proposal_error_page')
            ]);
        }
    }

    /**
     * Update propsal
     * Put proposals/{token}
     * @param  string $token token
     * @return proposal
     */
    public function updateProposal($token)
    {
        $input = Request::all();

        $validator = Validator::make($input, Proposal::getUpdateShareProposalRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $proposal = $this->model->whereToken($token)->firstOrFail();
        $input['job_id'] = $proposal->job_id;

        DB::beginTransaction();
        try {
            setScopeId($proposal->company_id);
            $proposal = $this->service->updateSharedProposal($proposal, $input);
        } catch(ProposalCannotBeUpdate $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Proposal']),
            'data' => $this->response->item($proposal, new ProposalsTransformer)
        ]);
    }

    /**
     * get proposal details by token
     * @return pdf
     */
    public function show($token)
    {

        $proposal = $this->model->whereToken($token)->firstOrFail();
        $transformers = (new ProposalsTransformer)->setDefaultIncludes(['pages']);

        return ApiResponse::success([
            'data' => $this->response->item($proposal, $transformers)
        ]);
    }

    /**
     * download or view pdf of proposal
     * @return pdf
     */
    public function getProposalFile($token)
    {
        $input = Request::onlyLegacy('download');

        try {
            $proposal = $this->model->whereToken($token)->firstOrFail();

            $fullPath = $proposal->getFilePathWithoutUrl();

            $fileResource = FlySystem::read($fullPath);

            $response = response($fileResource, 200);
            $response->header('Content-Type', $proposal->file_mime_type);
            $fileName = $proposal->file_name;
            if (!$input['download']) {
                $response->header('Content-Disposition', 'filename="' . $fileName . '"');
            } else {
                $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            }

            return $response;
        } catch (\Exception $e) {
            $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

            return view('error-page', [
                'errorDetail' => $errorDetail,
                'message' => trans('response.error.proposal_error_page'),
            ]);
        }
    }

    /*** Private functions ***/

    /**
     * get doenload thumb according to file mime type
     * @return $proposal with file type and full url
     */
    private function getDownloadThumb($mimeType)
    {
        $fullPath = config('app.url') . 'proposal_theme/images/';

        /* set ppt thumb*/
        if (in_array($mimeType, config('resources.powerpoint_types'))) {
            return $fullPath . 'ppt.png';
        }

        /* set doc thumb*/
        if (in_array($mimeType, config('resources.word_types'))) {
            return $fullPath . 'doc.png';
        }

        /* set xls thumb*/
        if (in_array($mimeType, config('resources.excel_types'))) {
            return $fullPath . 'xls.png';
        }

        /* set txt thumb*/
        if (in_array($mimeType, config('resources.text_types'))) {
            return $fullPath . 'txt.png';
        }

        /* set zip thumb*/
        if (in_array($mimeType, config('resources.compressed_file_types'))) {
            return $fullPath . 'zip.png';
        }
    }

    public function updateDataElements($token)
    {
        $input = Request::all();
        $validator = Validator::make($input, Proposal::getUpdateTemplateValueRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $proposal = $this->model->whereToken($token)->firstOrFail();

		if($proposal->status == Proposal::ACCEPTED) {

			return ApiResponse::errorGeneral(trans('response.error.proposal_cannot_update'));
        }

        try {
            setScopeId($proposal->company_id);

            $updatedProposal = $this->service->updateTemplateValue($proposal, $input['data_elements']);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Proposal']),
                'data' => $this->response->item($updatedProposal, new ProposalsTransformer)
            ]);
        } catch(ProposalCannotBeUpdate $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function updateComment($token)
	{
		$input = Request::onlyLegacy('comment');

		try{
			$proposal = $this->model->whereToken($token)->firstOrFail();
			$proposal->comment = $input['comment'];
			$proposal->save();

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Proposal Comment']),
			]);

		}catch(ModelNotFoundException $e) {
			return ApiResponse::errorGeneral(trans('response.error.not_found', ['attribute' => 'Proposal']));
		}catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
