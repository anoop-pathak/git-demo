<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidResourcePathException;
use App\Models\ApiResponse;
use App\Models\CustomerFeedback;
use App\Models\CustomerReview;
use App\Models\Email;
use App\Models\Job;
use App\Models\JobSchedule;
use App\Models\Message;
use App\Models\Resource;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Repositories\JobRepository;
use FlySystem;
use App\Services\Resources\ResourceServices;
use App\Services\Weather\Weather;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Helpers\CloudFrontSignedCookieHelper as CloudFront;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBooks\QuickBookService as QuickBookService;
use App\Models\JobInvoice;
use Settings;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\InvoicesOnCustomerJobPreviewTransformer as InvoicesTransformer;
use Illuminate\Support\Facades\DB;
use App\Models\YouTubeVideoLink;
use App\Services\Hover\HoverService;
use App\Exceptions\AccountNotConnectedException;
use Exception;
use Illuminate\Support\Facades\View;

class CustomerJobPreviewController extends ApiController
{

    protected $model;
    protected $jobRepo;
    protected $resourceService;
    protected $weatherService;
    protected $hoverService;

    public function __construct(
        Job $model,
        JobRepository $jobRepo,
        ResourceServices $resourceService,
        Weather $weatherService,
        QuickBookService $quickBook,
        Larasponse $response,
        HoverService $hoverService
    ) {

        $this->jobRepo = $jobRepo;
        $this->model = $model;
        $this->resourceService = $resourceService;
        $this->weatherService = $weatherService;
        $this->quickBook = $quickBook;
        $this->response = $response;
        $this->hoverService = $hoverService;

        parent::__construct();
    }

    /**
     * get job data
     * GET customer_job_preview/{token}
     * @param [string] $token [unique share_token of job]
     * @return false if operation failed else file.
     */
    public function show($token)
    {
        try {
            $job = Job::whereShareToken($token)->firstOrFail();
            $customer = $job->customer;

            $companyId = $job->company_id;

            setScopeId($companyId);

            $job = $this->jobRepo->getByShareToken($token);

            /*get lists of share tokens*/
            $jobsList = $this->model->whereCompanyId($companyId)
                ->whereCustomerId($job->customer_id)
                ->excludeProjects()
                ->whereNull('archived_cwp')
                ->with('trades')
                ->get();

            if (!$job->isMultiJob()) {
                $schedules = $job->schedules;
            } else {
                $projectIds = $job->projects->pluck('id')->toArray();
                $schedules = JobSchedule::whereIn('job_id', $projectIds)
                    ->recurring()
                    ->get();
            }

            /*Get Job 3D Model*/
			$hover3DModel = '';
			if($job->sync_on_hover) {
				$hover3DModel = $this->hover3DModel($job);
			}

            /*get resources*/
            $jobResourceId = $job->getResourceId();
            $resources = $this->getResources($jobResourceId);

            /*get counts*/
            $counts = $this->getCounts($job);

            /*get weather*/

            $weather = $this->getWeather($job->address);

            /*get social links*/
            $socialLinks = $this->getSocialLinks($companyId);
            $customerRep = $customer->rep;

            $rejectedProposals = $job->rejectedCWPProposals;
            $acceptedProposals = $job->acceptedCWPProposals;
            $pendingProposals = $job->pendingCWPProposals;
            $sharedEstimates = $job->sharedEstimates;
            $customerReview = $job->customerReview;
            //get google review link
            $googleCustomerReviewLink = null;
            $placeId = Settings::get('GOOGLE_CUSTOMER_REVIEW_PLACE_ID');
            $websiteLink = Settings::get('WEBSITE_LINK');

            if ($placeId) {
                $googleCustomerReviewLink = config('jp.google_customer_review_link') . $placeId;
            }

            $quickbooksConnected = $this->quickBook->isConnected();
            $quickbookPaymentsConnected = $this->quickBook->isPaymentsConnected();

            $jobTrades = $job->trades->pluck('id')->toArray();

            if($job->isMultiJob()) {
				$projectIds = $job->projects->pluck('id')->toArray();
				$jobTrades = DB::table('job_trade')
					->whereIn('job_id',$projectIds)
					->pluck('trade_id')->toArray();
			}

            $youtubeVideos = YouTubeVideoLink::where('company_id', $companyId)->Where('for_all_trades', '=', true)->get();

            // set headers for cloud front cookies..
            CloudFront::setCookies();

            if($job->isMultiJob()) {
                return view('jobs.customer-job-preview-multi', compact('job', 'jobsList', 'resources', 'counts', 'weather', 'socialLinks', 'schedules', 'customerRep', 'pendingProposals', 'googleCustomerReviewLink', 'customerReview', 'placeId', 'acceptedProposals', 'rejectedProposals',  'sharedEstimates', 'quickbooksConnected', 'quickbookPaymentsConnected', 'youtubeVideos', 'websiteLink'));
            }

            $youtubeVideos = YouTubeVideoLink::where('company_id', $companyId)
                ->where(function($query) use($job, $jobTrades) {
                    $query->whereIn('id', function($query) use($job, $jobTrades) {
                        $query->select('youtube_video_link_id')
                            ->from('youtube_video_link_trades')
                            ->whereIn('youtube_video_link_trades.trade_id', $jobTrades);
                    })
                    ->orWhere('youtube_video_links.for_all_trades', '=', true);
                })
                ->get();

            return view('jobs.customer-job-preview', compact('job', 'jobsList', 'resources', 'counts', 'weather', 'socialLinks', 'schedules', 'customerRep', 'pendingProposals', 'googleCustomerReviewLink', 'customerReview', 'placeId', 'acceptedProposals', 'rejectedProposals',  'sharedEstimates', 'quickbooksConnected', 'quickbookPaymentsConnected', 'youtubeVideos', 'hover3DModel', 'websiteLink'));

        } catch (\Exception $e) {
            return $this->getErrorDetails($e);
        }
    }

    public function invoices($token)
    {
        $job = Job::whereShareToken($token)->firstOrFail();
        setScopeId($job->company_id);
        $invoices = $job->invoices;
        $invoices = $this->response->collection($invoices, new InvoicesTransformer);

        return ApiResponse::success($invoices);
    }

    /**
     * get file resource
     * GET customer_job_preview/get_resource_file
     * @param
     * @return false if operation failed else file.
     */
    public function getResourceFile()
    {
        $input = Request::onlyLegacy('id', 'download', 'base64_encoded');

        $validator = Validator::make($input, Resource::getFileRules());
        if ($validator->fails()) {
            return view('error-page')->with('errorDetail', $validator->errors());
        }

        try {
            $file = $this->resourceService->getFile(
                $input['id'],
                (bool)$input['download'],
                (bool)$input['base64_encoded']
            );
        } catch (InvalidResourcePathException $e) {
            return $this->getErrorDetails($e);
        } catch (InvalidFileException $e) {
            return $this->getErrorDetails($e);
        } catch (\Exception $e) {
            return $this->getErrorDetails($e);
        }
        if ((bool)$input['base64_encoded']) {
            return ApiResponse::success(['data' => $file]);
        }

        return $file;
    }

    /**
     * add feedabck (testimonial or complaint)
     * PUT customer_job_preview/feedabck
     * @param
     * @return false if operation failed else file.
     */
    public function saveFeedback()
    {
        $input = Request::all();

        $validator = Validator::make($input, CustomerFeedback::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::whereShareToken($input['share_token'])->firstOrFail();

        try {
            $input['company_id'] = $job->company_id;
            $input['job_id'] = $job->id;
            $input['customer_id'] = $job->customer_id;

            CustomerFeedback::create($input);

            // send mail to company owner
            $this->sendEmailToOwner($input, $job);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        $attribute = 'Contact Us / Issues';
        if($input['type'] == 'testimonial') {
            $attribute =  'Testimonial';
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => $attribute])
        ]);
    }

    /**
     * get weather
     * GET customer_job_preview/weather
     *
     * @param $adress
     * @return $weather
     */
    public function getWeather($address)
    {
        if(!$address || (!$country = $address->country) || (!$address->city && !$address->zip)) return false;
        $location = '';
        
        try {
            if($address->city) {
                $location = $address->city;
            }elseif($address->zip) {
                $location = $address->zip;
            }

            $weather = $this->weatherService->getWeather($location, $country->code);

            return $weather;
        } catch (\Exception $e) {
            //handle exception
            Log::warning('Home Owner Page Weather: ' . getErrorDetail($e));
        }
    }

    /**
     * get job invoice
     * GET customer_job_preview/job_invoice/{id}
     *
     * @param
     * @return
     */
    public function getJobInvoice($id)
    {
        $input = Request::onlyLegacy('token', 'download');
        $validator = Validator::make($input, ['token' => 'required']);
        if ($validator->fails()) {
            return view('error-page')->with('errorDetail', $validator->errors());
        }

        try {
            $job = $this->model->whereShareToken($input['token'])
                ->firstOrFail();


            $invoice = JobInvoice::whereJobId($job->id)
                ->whereId($id)
                ->firstOrFail();

            $filePath = config('jp.BASE_PATH') . $invoice->file_path;

            if (!$invoice->file_size) {
                $filePath = 'public/' . $invoice->file_path;
            }

            $fileResource = FlySystem::read($filePath);

            $response = \response($fileResource, 200);

            $response->header('Content-Type', 'application/pdf');
            if (!$input['download']) {
                $response->header('Content-Disposition', 'filename="' . $invoice->title . '"');
            } else {
                $response->header('Content-Disposition', 'attachment; filename="' . $invoice->title . '"');
            }

            return $response;
        } catch (\Exception $e) {
            return $this->getErrorDetails($e);
        }
    }

    /**
     * share job review to customer
     * POST jobs/{id}/share_with_customer
     *
     * @param $token [string] share_token of job
     * @return
     */
    public function share($id)
    {
        $job = $this->jobRepo->getById($id);

        /* set customer (receiver) email */
        $email = $job->customer->email;
        if (!$email) {
            return ApiResponse::errorGeneral(trans('response.error.set_customer_email'));
        }

        try {
            /* set email subject */
            $subject = '[' . $job->company->name . '] Job Preview';

            /* get email content */
            $content = $this->getContent($job);

            App::make(\App\Services\Emails\EmailServices::class)->sendEmail(
                $subject,
                $content,
                (array)$email,
                [],
                [],
                [],
                \Auth::id(),
                []
            );

            return ApiResponse::success(['message' => trans('response.success.email_sent')]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Add job review
     * Post /add_job_review
     */
    public function addJobReview()
    {
        $input = Request::onlyLegacy('share_token', 'rating', 'comment');
        $job = Job::whereShareToken($input['share_token'])
            ->whereNotNull('share_token')
            ->firstOrFail();

        $validator = Validator::make($input, CustomerReview::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $review = CustomerReview::firstOrNew([
                'job_id' => $job->id,
            ]);
            $review->customer_id = $job->customer_id;
            $review->rating = $input['rating'];
            $review->comment = $input['comment'];
            $review->save();

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Customer review'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /***** Private Functions *****/

    /**
     * get social links of company
     *
     * @param $companyId [int]
     * @return
     */
    private function getSocialLinks($compnayId)
    {
        return Setting::whereCompanyId($compnayId)
            ->where('key', 'SOCIAL_LINKS')
            ->whereNotNull('value')
            ->select('value')
            ->first();
    }

    /**
     * get counts related to job entities
     *
     * @param $job [Job instance]
     * @return $count [array]
     */
    private function getCounts($job)
    {
        $count = [];
        if ($job->isMultiJob()) {
            $jobIds = $job->projects->pluck('id')->toArray();
            $jobIds[] = $job->id;

            $emailCount = Email::where('emails.company_id', getScopeId())->whereHas('jobs', function ($query) use ($jobIds) {
                $query->whereIn('jobs.id', $jobIds);
            })->count();

            $messageCount = Message::whereIn('job_id', $jobIds)->count();

            $upcomingTaskCount = Task::whereIn('job_id', $jobIds)->upcoming()->count();
        } else {
            $emailCount = $job->emails()->count();
            $messageCount = $job->messages()->count();
            $upcomingTaskCount = $job->tasks()->upcoming()->count();
        }

        $count['messages'] = $messageCount;
        $count['emails'] = $emailCount;
        $count['upcoming_tasks'] = $upcomingTaskCount;

        return $count;
    }

    /**
     * get job resources
     *
     * @param $resourceId [int]
     * @return $resources [array]
     */
    private function getResources($resourceId)
    {
        /* get resource files */
        try {
            /* get shared resource files */
            $resources['files'] = $this->resourceService->getSharedFiles($resourceId, ['mime_type' => 'files']);

            /* get shared resource shared images */
            $resources['images'] = $this->resourceService->getSharedFiles($resourceId, ['mime_type' => 'images']);

            return $resources;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * set eamil content
     * @return content
     */
    private function getContent($job)
    {
        $url = config('app.url') . 'customer_job_preview/' . $job->share_token;

        /*set email content*/
        $content = '<p>Dear ' . $job->customer->full_name . ',</p><p>You can review the customer web page by clicking the button below.</p><p style="margin-top:20px;"><a href=' . $url . ' style="text-decoration:none;color: #FFF;background-color: #337AB7;border: 1px solid #2E6DA4;border-radius: 4px;padding: 6px 12px;text-align: center;white-space: nowrap;">Customer Web Page</a></p><p>&nbsp;</p><p>Thanks</p><p>' . $job->company->name . '</p>';

        return $content;
    }

    /**
     * get error datails
     * @param $e [Exception type class instance]
     * @return View
     */
    private function getErrorDetails($e)
    {
        $errorDetail = $e->getLine() . ' ' . $e->getFile() . ' ' . $e->getMessage();

        return view('error-page', [
            'errorDetail' => $errorDetail,
            'message' => trans('response.error.error_page'),
        ]);
    }

    /**
     * send email to owner
     * @param  Array $input
     * @param  Job $job
     * @return
     */
    private function sendEmailToOwner($input, $job)
    {
        $customer = $job->customer;

        $name = " ({$customer->full_name} / {$job->number})";
        $subject = 'New Testimonial - ' . $name;

        if ($input['type'] == 'complaint') {
            $subject = 'Issue - ' . $name;
        }

        $owner = User::whereCompanyId($job->company_id)
            ->whereGroupId(User::GROUP_OWNER)
            ->firstOrFail();

        $data = [
            'owner' => $owner,
            'job' => $job,
            'input' => $input,
        ];

        Mail::send('emails.owner.customer_feedback', $data, function ($mail) use ($owner, $subject) {
            $mail->to($owner->email)
                ->subject($subject);
        });
    }

    /**
     * Show QuickBooks Payments Page
     * @param int $invoice_id
     * @return View
     */
    public function quickBooksPaymentPage()
    {
        $invoices = Request::input('invoices');
        $redirectWeb = Request::exists('redirect-web');
        if(empty($invoices)) {
            return [
                'message' => 'No invoices sent in Request'
            ];
        }
        # this is to ensure if invoices selected are from different jobs then return error
        $jobsInvoice = JobInvoice::whereIn('id', $invoices);
        $jobsCount = clone $jobsInvoice;
        $jobsCount = $jobsCount->groupBy('job_id')->get(['job_id'])->count();
        if($jobsCount != 1) {
            return [
                'message' => 'Invoices sent in Request belong to more than 1 job'
            ];
            // this needs to be discussed and sorted
            // \App::abort(404);
        }
        # check the payment is equal to total of the invoices amount
        // Here invoice amount - payment recieved must be available
        $amount = 0;
        $invoices = clone $jobsInvoice;
        $invoices  = $invoices->get();
        foreach ($invoices as $invoice) {
            $amount += $invoice->open_balance;
        }
        $jobInvoices = $jobsInvoice->get(['id']);
        $years = range(date('Y'), 2040);
        $years = array_combine($years, $years);
        if($amount > 0) {
            return view('quickbooks.payment-checkout', [
                'amount'    => $amount,
                'invoices'  => $jobInvoices,
                'years'     => $years,
                'redirectWeb' => $redirectWeb
            ]);
        } else {
            return view('quickbooks.payment-zero', ['amount' => $amount]);
        }
    }

    /**
     * Show QuickBooks Payments Page
     * @param int $invoice_id
     * @return View
     */
    public function quickBooksPaymentPageMobile()
    {
        $invoices = Request::input('invoices');
        return View::make('quickbooks.payment-mobile');
    }

    /**
	 * Hover 3D Model
	 */
	private function hover3DModel($job)
	{
		try {

			return $this->hoverService->shareHoverJob($job);
		} catch(AccountNotConnectedException $e){

		} catch (Exception $e) {
			Log::error($e);
		}
	}
}
