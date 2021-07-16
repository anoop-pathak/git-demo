<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\TempProposalPage;
use App\Services\Contexts\Context;
use App\Transformers\TempProposalPageTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Models\PageTableCalculation;

class TempProposalPagesController extends ApiController
{

    /**
     * Larasponse $response
     * @var Sorskod\Larasponse\Larasponse Instance
     */
    protected $response;

    /**
     * Company Scope
     * @var App\Contexts\Context Instance
     */
    protected $scope;

    public function __construct(Larasponse $response, Context $scope)
    {
        $this->response = $response;
        $this->scope = $scope;

        if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

        parent::__construct();
    }

    /**
     * Temp proposal page save
     * Post /proposals/temp_page
     * @return Temp page
     */
    public function store()
    {
        $input = Request::onlyLegacy('title', 'content', 'auto_fill_required', 'page_type', 'tables');

        $validator = Validator::make($input, TempProposalPage::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $temp = TempProposalPage::create([
            'company_id' => $this->scope->id(),
            'title' => $input['title'],
            'content' => $input['content'],
            'auto_fill_required' => $input['auto_fill_required'],
            'page_type'	=> ine($input, 'page_type') ? $input['page_type'] : null
        ]);

        if(ine($input, 'tables')) {
			foreach ((array)$input['tables'] as $table) {
				$pageTableCalculation = PageTableCalculation::create([
					'page_type' => PageTableCalculation::TEMP_PROPOSAL_PAGE,
					'type_id' => false,
					'page_id' => $temp->id,
					'name'	  => isset($table['name']) ? $table['name'] : null,
					'ref_id'  => $table['ref_id'],
					'head'    => $table['head'],
					'body'    => $table['body'],
					'foot'    => $table['foot'],
					'options' => ine($table, 'options') ? $table['options']  : [],
				]);
			}
		}

        return ApiResponse::success([
            'data' => $this->response->item($temp, new TempProposalPageTransformer)
        ]);
    }

    /**
     * Show temp proposal page
     * Get /proposals/temp_page/{id}
     * @param  Int $id Temp proposal page id
     * @return temp proposal page
     */
    public function show($id)
    {
        $temp = TempProposalPage::whereCompanyId($this->scope->id())->findOrFail($id);

        return ApiResponse::success([
            'data' => $this->response->item($temp, new TempProposalPageTransformer)
        ]);
    }

    /**
     * Update temp proposal page
     * Get /proposals/temp_page/{id}
     * @param  Int $id Temp proposal page id
     * @return temp proposal page
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('title', 'content', 'auto_fill_required', 'page_type');
        $temp = TempProposalPage::whereCompanyId($this->scope->id())->findOrFail($id);

        $validator = Validator::make($input, TempProposalPage::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $data = [
            'title' => $input['title'],
            'content' => $input['content'],
            'auto_fill_required' => $input['auto_fill_required'],
        ];

        if(isset($input['page_type'])) {
			$data['page_type'] = $input['page_type'];
		}

        $temp->update($data);

        return ApiResponse::success([
            'data' => $this->response->item($temp, new TempProposalPageTransformer)
        ]);
    }

    /**
     * Destroy multiple proposal page
     * Delete /proposals/temp_page
     * @return Response
     */
    public function destroy()
    {
        $input = Request::onlyLegacy('ids');

        $validator = Validator::make($input, ['ids' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        TempProposalPage::whereIn('id', (array)$input['ids'])
            ->whereCompanyId($this->scope->id())
            ->delete();

        return ApiResponse::success();
    }
}
