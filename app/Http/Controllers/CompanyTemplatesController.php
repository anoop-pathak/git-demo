<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\Folders\MoveFileService;
use App\Repositories\TemplatesRepository;
use App\Services\Folders\MoveTemplateToOtherCompany;
use App\Models\Template;
// use MaterialList;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\DB;
use Exception;
use FlySystem;
use Request;

class CompanyTemplatesController extends ApiController
{

	protected $repo;
	protected $service;
	protected $response;

	public function __construct(MoveFileService $service,
        TemplatesRepository $repo,
        Larasponse $response)
	{
		parent::__construct();
		$this->repo = $repo;
		$this->service = $service;
		$this->response = $response;
	}
	/**
	 * move files
	 * GET /company/{company_id}/move_templates
	 *
	 * @return Response
	 */
	public function moveFiles($companyId)
	{
		$input = Request::only('template_ids', 'password');

		$validator = Validator::make($input,['template_ids' => 'required', 'password' => 'required']);
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

		$password = config('jp.move_templates_to_other_company_password');
		if($input['password'] != $password) {
			return ApiResponse::errorGeneral(trans('response.error.incorrect_password'));
		}

		DB::beginTransaction();
		try {
			$templateIds = $input['template_ids'];
			$templates = Template::whereIn('id', $templateIds)->get();

			$items = [];
			foreach($templates as $template) {
				setScopeId($template->company_id);
				$helper = new MoveTemplateToOtherCompany;
				$items[] = $helper->company($companyId)
                    ->type($template->type)
                    ->templateId($template->id)
                    ->move();
			}
			DB::commit();
			return ApiResponse::success($items);
		} catch (Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function getFileContent()
	{
		$input = Request::onlyLegacy('file_url');

		$validator = Validator::make($input, ['file_url' => 'required']);
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		try {
			$fileUrl = $input['file_url'];
			$url = parse_url($fileUrl);
			$content = FlySystem::read($url['path']);

			return $content;
		} catch (Exception $e) {
			return ApiResponse::errorGeneral(trans('File Url is Invalid.'));
		}
	}
}