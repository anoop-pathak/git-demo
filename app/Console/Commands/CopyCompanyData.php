<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Events\Folders\TemplateStoreFile;
use App\Models\Company;
use App\Models\User;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\EmailTemplate;
use App\Models\Template;
use App\Models\FinancialMacro;
use App\Models\TemplatePage;
use App\Models\EmailTemplateRecipient;
use App\Models\MacroDetail;
use Exception;
use FlySystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\App;


class CopyCompanyData extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:copy_company_data';

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
		// $copyFromCompanyId = 1539;
		// $copyToCompanyId = 1974;
		$copyFromCompanyId = $this->ask("Please enter company id from which you want to copy the records: ");
		$fromCompany = Company::findOrFail($copyFromCompanyId);

		$copyToCompanyId = $this->ask("Please enter company id in which you want to copy the records: ");

		$toCompany = Company::findOrFail($copyToCompanyId);

		$user = User::where('group_id', User::GROUP_OWNER)
			->where('company_id', $copyToCompanyId)
			->first();

		$categoryNames = ['FLOOR', 'CABINET', 'CAR LIFT', 'WHOLESALE CABINETS'];
		$categoryIds = FinancialCategory::where('company_id', $copyFromCompanyId)
			->whereIn('name', $categoryNames)
			->pluck('id')
			->toArray();

		$financialProducts = FinancialProduct::where('company_id', $copyFromCompanyId)
			->whereIn('category_id', $categoryIds)
			->get();

		$financialProductData = [];
		$startedAt = $currentDateTIme = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: $startedAt -----");

		DB::beginTransaction();
		try{
			foreach ($financialProducts as $financialProduct) {
				$category = FinancialCategory::where('company_id', $copyFromCompanyId)
					->where('id', $financialProduct->category_id)
					->first();
				$categoryId = $this->getCategoryId($category->name, $copyToCompanyId, $category->default);

				$fProduct = new FinancialProduct;
				$fProduct->name    = $financialProduct->name;
				$fProduct->company_id    = $copyToCompanyId;
				$fProduct->category_id    = $categoryId;
				$fProduct->unit    = $financialProduct->unit;
				$fProduct->unit_cost    = $financialProduct->unit_cost;
				$fProduct->code    = $financialProduct->code;
				$fProduct->description    = $financialProduct->description;
				$fProduct->selling_price = $financialProduct->selling_price;
				$fProduct->supplier_id 	= $financialProduct->supplier_id;
				$fProduct->abc_additional_data = $financialProduct->abc_additional_data;
				$fProduct->styles		= $financialProduct->styles;
				$fProduct->sizes			= $financialProduct->sizes;
				$fProduct->colors		= $financialProduct->colors;
				$fProduct->labor_id		= $financialProduct->labour_id;
				$fProduct->qb_desktop_id = null;
				$fProduct->trade_id		= $financialProduct->trade_id;
				$fProduct->active		= 1;
				$fProduct->created_at	= $currentDateTIme;
				$fProduct->updated_at	= $currentDateTIme;
				$fProduct->save();
			}
			$this->info("----- Financial Products Added -----");

			$this->copyEmailTemplates($copyFromCompanyId, $copyToCompanyId, $user);
			$this->info("----- Email Templates Added -----");


			$this->copyMacros($copyFromCompanyId, $copyToCompanyId, $user);
			$this->info("----- Macros Added -----");

			$this->copyProposalTemplates($copyFromCompanyId, $copyToCompanyId, $user);
			$this->info("----- Proposal Templates Added -----");

		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		DB::commit();

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Command completed at: $completedAt -----");


	}

	private function copyEmailTemplates($companyId, $copyToCompanyId, $user)
	{
		// $companyId = 1539;
		// $copyToCompanyId = 1974;
		$emailTemplates = EmailTemplate::where('company_id', $companyId)->get();
		$emailData = [];
		foreach ($emailTemplates as $template) {
			$stageCode = null;
			if($template->stage_code){
				$stageCode = $this->getStageCode($template->stage_code);
			}
			$emailTemplate  = new EmailTemplate;
			$emailTemplate->title      = $template->title;
			$emailTemplate->template   = $template->template;
			$emailTemplate->active     = $template->active;
			$emailTemplate->created_by = $user->id;
			$emailTemplate->company_id = $copyToCompanyId;
			$emailTemplate->subject    = $template->subject;
			$emailTemplate->stage_code = $stageCode;
			$emailTemplate->send_to_customer = $template->send_to_customer;
			$emailTemplate->recipients_setting = (array)$template->recipients_setting;
			$emailTemplate->save();

			$this->attachTemplateRecipient($template, $emailTemplate->id);
		}

	}

	private function copyProposalTemplates($companyId, $copyToCompanyId, $user)
	{
		// $companyId = 1539;
		// $copyToCompanyId = 1974;
		$templates = Template::where('company_id', $companyId)->where('type', Template::PROPOSAL)->get();
		foreach ($templates as $temp) {

			$template = new Template;
			$template->title 				=	$temp->title;
			$template->type 				=	$temp->type;
			$template->created_by 			=	$user->id;
			$template->company_id 			=	$copyToCompanyId;
			$template->option 				=	$temp->option;
			$template->insurance_estimate	=	$temp->insurance_estimate;
			$template->all_divisions_access = 	$temp->all_divisions_access;
			$template->page_type    		= 	$temp->page_type;
			$template->for_all_trades		=	$temp->for_all_trades;
			$template->save();

			$pages  = $temp->pages;
			foreach ($pages as $key => $page) {
				$content = $page->content ? $page->content : "";
				$order = $key + 1;
				$this->createPage($template, $content, $order, $page);
			}
			setAuthAndScope($user->id);
			$eventData = [
				'reference_id' => $template->id,
				'name' => $template->id,
				'parent_id' => null,
				'type' => $template->type,
			];
			Event::fire('JobProgress.Templates.Events.Folder.storeFile', new TemplateStoreFile($eventData));
		}
	}

	private function copyMacros($companyId, $copyToCompanyId, $user)
	{
		// $companyId = 1539;
		// $copyToCompanyId = 1974;
		$macros = FinancialMacro::where('company_id', $companyId)->get();
		foreach ($macros as $mac) {
			$macro = new FinancialMacro;
			$macro->company_id =$copyToCompanyId;
			$macro->macro_id = generateUniqueToken();
			$macro->macro_name	   = $mac->macro_name;
			$macro->type		   = $mac->type;
			$macro->trade_id       = $mac->trade_id;
			$macro->for_all_trades = $mac->for_all_trades;
			$macro->branch_code	   = $mac->branch_code;
			$macro->order          = $this->getPreviousMacroOrder($copyToCompanyId);;
			$macro->all_divisions_access = $mac->all_divisions_access;
			$macro->fixed_price  = $mac->fixed_price;
			$macro->save();

			$details = $mac->macroDetails;

			foreach ($details as $detail) {

				$category = FinancialCategory::where('company_id', $companyId)
					->where('id', $detail->category_id)
					->first();
				$product = FinancialProduct::where('company_id', $companyId)
					->where('category_id', $category->id)
					->where('id', $detail->product_id)
					->first();

				$categoryId = $this->getCategoryId($category->name, $copyToCompanyId, $category->default);
				$data = [];
				$data= [
					'product_id'	=> $this->getProductId($product, $category, $companyId, $copyToCompanyId),
					'category_id'	=> $categoryId,
					'order'			=> $this->getDetailsOrder($macro, $categoryId, $copyToCompanyId),
					'quantity'		=> $detail->quantity,
					'macro_link_id' => $macro->id,
					'macro_id'      => $macro->macro_id,
					'company_id'	=> $copyToCompanyId,
				];
				DB::table('macro_details')->insert($data);
			}
		}
	}

	private function getProductId($product, $category, $companyId, $copyToCompanyId)
	{
		$pId = null;

		if(!$product) return $pId;

		$catId = $this->getCategoryId($category->name, $copyToCompanyId, $category->default);

		$financialProduct = FinancialProduct::where('company_id', $copyToCompanyId)
			->where('name', $product->name)
			->where('category_id', $catId)
			->first();

		return $financialProduct ? $financialProduct->id: null;
	}


	private function createPage($template, $content, $order, $refPage) {
		$fileName = $this->createThumb($content, $template);
		$page = TemplatePage::create([
			'template_id'		=> $template->id,
			'content' 			=> $content,
			'image'  			=> 'templates/'.$fileName,
			'thumb'  			=> 'templates/thumb/'.$fileName,
			'editable_content'  => $refPage->editable_content,
			'order'				=> $order,
			'auto_fill_required' => $refPage->auto_fill_required
		]);
	}

	private function createThumb($templateContent, $template) {

		$pageType =  $template->page_type;

		if($template->type == 'proposal') {
			$pageType .= ' legal-size-proposal a4-size-proposal';
		}else {
			$pageType .= ' legal-size-estimate a4-size-estimate';
		}
		$contents = \View::make('templates.template', [
			'content' => $templateContent,
			'pageType' => $pageType
		])->render();

		$filename  = Carbon::now()->timestamp.rand().'.jpg';
		$imageBaseName = 'templates/' . $filename;
		$thumbBaseName = 'templates/thumb/' . $filename;
		$imageFullPath = config('jp.BASE_PATH').$imageBaseName;
		$thumbFullPath = config('jp.BASE_PATH').$thumbBaseName;

		$snappy = App::make('snappy.image');
		$snappy->setOption('width', '794');
		if($pageType == 'legal-page') {
			$snappy->setOption('height', '1344');
		}else {
			$snappy->setOption('height', '1122');
		}
		$image = $snappy->getOutputFromHtml($contents);

		// save image...
		FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);

		// resize for thumb..
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
		// save thumb ..
		FlySystem::put($thumbFullPath, $image->encode()->getEncoded());

		return $filename;
	}

	private function getCategoryId($name, $companyId, $default = false)
	{
		// switch ($value) {
		// 	case 9131:
		// 		$categoryId = 11266;
		// 		break;
		// 	case 9132:
		// 		$categoryId = 11267;
		// 		break;
		// 	case 9133:
		// 		$categoryId = 11268;
		// 		break;
		// 	case 9816:
		// 		$categoryId = 11269;
		// 		break;
		// 	default:
		// 		$categoryId = null;
		// 		break;
		// }
		$category = FinancialCategory::where('company_id', $companyId)
			->where('name', $name)
			->first();

		if(!$category){
			$lastCategory = FinancialCategory::whereCompanyId($companyId)
				->orderBy('order', 'desc')
				->select('order')
				->first();

			$order = 1;
			if($lastCategory) {
				$order = $lastCategory->order + 1;
			}

			$category = FinancialCategory::create([
				'name' 		 => $name,
				'default' 	 => $default,
				'company_id' => $companyId,
				'order'		 => $order,
			]);
		}

		return $category->id;
	}

	private function getStageCode($code){

		switch ($code) {
			case '15873361741025446009':
				$stageCode = null;
				break;
			case '1587521499128482128':
				$stageCode = '244145296';
				break;
			case '15877637561247838017':
				$stageCode = '42461497';
				break;
			case '1589426768955812366':
				$stageCode = '127149926';
				break;
			case '15898311831989809369':
				$stageCode = '1602521932327835612';
				break;
			case '696664551':
				$stageCode = '56712977';
				break;
			case '927491360':
				$stageCode = '16025219321495743647';
				break;
			default:
				$stageCode = null;
				break;
		}

		return $stageCode;
	}

	private function attachTemplateRecipient($emailTemplate, $templateId)
	{
		$emails = EmailTemplateRecipient::where('email_template_id', $emailTemplate->id)->get();

		foreach ($emails as $email) {
			EmailTemplateRecipient::create([
			 	'email_template_id' => $templateId,
			 	'email'             => $email->email,
			 	'type'              => $email->type
			]);
		}
	}

	private function getPreviousMacroOrder($companyId)
	{
		$order = FinancialMacro::latest('order')->where('company_id', '=', $companyId)->first();
		if(!$order) return 1;

		return $order->order + 1;
	}

	private function getDetailsOrder($macro, $categoryId, $companyId)
	{
		$order = MacroDetail::latest('order')
			->where('company_id', '=', $companyId)
			->where('category_id', '=', $categoryId)
			->where('macro_link_id', $macro->id)
			->first();
		if(!$order) return 1;

		return $order->order + 1;
	}
}
