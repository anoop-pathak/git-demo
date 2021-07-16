<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalideAttachment;
use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\Email;
use App\Models\EmailLabel;
use App\Services\Contexts\Context;
use App\Services\Emails\EmailBounceHandler;
use App\Services\Emails\EmailServices;
use Firebase;
use App\Transformers\CompanyContactTransformer;
use App\Transformers\EmailsTransformer;
use App\Transformers\Optimized\CustomersTransformer;
use App\Transformers\Optimized\UsersTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use PDF;
use App\Models\Contact;

class EmailsController extends ApiController
{

    /**
     * Email Service
     * @var \App\Services\Emails\EmailServices
     */
    protected $emailService;

    /**
     * Larasponse Response
     * @var Sorskod\Larasponse\Larasponse;
     */
    protected $response;
    /**
     * company scope
     * @var [Context]
     */
    protected $scope;

    public function __construct(
        EmailServices $emailService,
        Larasponse $response,
        Context $scope,
        EmailBounceHandler $bounceHandler
    ) {
        $this->emailService = $emailService;
        $this->response = $response;
        $this->scope = $scope;
        $this->bounceHandler = $bounceHandler;
        Request::merge(['disable_division' => true]);

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Send Email
     * POST /emails/send
     *
     * @return Response
     */
    public function send()
    {
        $input = Request::onlyLegacy(
            'subject',
            'content',
            'to',
            'cc',
            'bcc',
            'attachments',
            'customer_id',
            'job_id',
            'reply_to',
            'stage_code',
            'main_job_id',
            'proposal_id'
        );

        $validator = Validator::make($input, Email::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        //Remove Html Special Characters;
		$content = str_replace(" ", "", strip_tags(preg_replace("/&#?[a-z0-9]+;|\n/","",$input['content'])));
		if(empty($content)) {
			return ApiResponse::errorGeneral(trans('validation.required',['attribute' => 'content']));
		}

        try {
            $email = $this->emailService->sendEmail(
                $input['subject'],
                $input['content'],
                (array)$input['to'],
                (array)$input['cc'],
                (array)$input['bcc'],
                (array)$input['attachments'],
                Auth::id(),
                $input
            );
        } catch (InvalideAttachment $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.email_sent'),
            'email' => $this->response->item($email, new EmailsTransformer),
        ]);
    }

    /**
     * Get sent emails
     * Get /emails/sent
     *
     * @return Response
     */
    public function sent_emails()
    {

        switchDBConnection('mysql2');

        $input = Request::all();

        // if(ine($input,'with_reply')) {
        // 	$input['type'] = Email::RECEIVED;
        // }else {
        //  $input['type'] = Email::SENT;
        // }

        $emails = $this->emailService->getEmails($input);
        $limit = isset($input['limit']) ? $input['limit'] : \config('jp.pagination_limit');
        if (!$limit) {
            $emails = $emails->get();
            return ApiResponse::success($this->response->collection($emails, new EmailsTransformer));
        }
        $emails = $emails->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($emails, new EmailsTransformer));
    }

    /**
     * Get sent email
     * Get /emails/sent/{id}
     *
     * @return Response
     */
    public function show($id)
    {

        $email = $this->emailService->getById($id);

        $unreadEmailCount = Email::whereConversationId($email->conversation_id)
            ->whereCreatedBy(Auth::id())
            ->whereIsRead(false)
            ->count();

        if ($unreadEmailCount) {
            Email::whereConversationId($email->conversation_id)
                ->whereCreatedBy(Auth::id())
                ->update(['is_read' => true]);

            Firebase::updateUserEmailCount(Auth::id());
        }

        // read thread..
        switchDBConnection('mysql2');
        if (Request::get('recursive')) {
            $thread = $email->getThread();
            return ApiResponse::success($this->response->collection($thread, new EmailsTransformer));
        }
        return ApiResponse::success([
            'data' => $this->response->item($email, new EmailsTransformer)
        ]);
    }

    /**
     * Mark As Read
     * Post /emails/read
     *
     * @return Response
     */
    public function markAsRead()
    {
        $input = Request::onlyLegacy('email_ids', 'is_read');
        $validator = Validator::make($input, Email::getMarkAsReadRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $this->emailService->markAsRead($input['email_ids'], $input['is_read']);
            if ($input['is_read']) {
                return ApiResponse::success([
                    'message' => trans('response.success.mark_read'),
                ]);
            }

            return ApiResponse::success([
                'message' => trans('response.success.mark_unread'),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Unread Mail Count
     * Get /emails/unread_count
     *
     * @return Response
     */
    public function getUnreadMailCount()
    {
        $input['type'] = Email::UNREAD;
        $input['with_reply'] = true;
        $input['not_moved'] = true;
        try {
            $emails = $this->emailService->getEmails($input);
            $data['unread_count'] = $emails->get()->count();
            return ApiResponse::success([
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Customers and Users Contact list
     * Get /emails/contacts
     *
     * @return Response
     */
    public function contacts_list()
    {
        $input = Request::all();
        $usersRepo = App::make(\App\Repositories\UserRepository::class);
        $customersRepo = App::make(\App\Repositories\CustomerRepository::class);
        $companyContactsRepo = App::make(\App\Repositories\ContactRepository::class);
        $labourRepo = App::make(\App\Repositories\LabourRepository::class);

        if(Auth::user()->isSubContractorPrime()) {
            return ApiResponse::success([
                'data' => [
                    'customers'         => [],
                    'users'             => [],
                    'company_contacts'  => [],
                    'labors'            => [],
                ]
            ]);
        }

        // get customers, company contacts, labours and users
        $customers = $customersRepo->getFilteredCustomers($input);
        $users = $usersRepo->getFilteredUsers($input);
        $companyContacts = $companyContactsRepo->getFilteredContacts($input, Contact::TYPE_COMPANY);
        $companyContacts->with(['emails']);
        $labours = $labourRepo->getLabours($input);
        // pagination limit
        $limit = ine($input, 'limit') ? $input['limit'] : \config('jp.pagination_limit');

        $customerTrans = new CustomersTransformer;
        $customerTrans->setDefaultIncludes([]);

        $companyContactTransformer = new CompanyContactTransformer;
		$companyContactTransformer->setDefaultIncludes(['emails']);

        return ApiResponse::success([
            'data' => [
                'customers' => $this->response->paginatedCollection($customers->paginate($limit), $customerTrans)['data'],
                'users' => $this->response->paginatedCollection($users->paginate($limit), new UsersTransformer)['data'],
                'company_contacts' => $this->response->paginatedCollection($companyContacts->paginate($limit), $companyContactTransformer)['data'],
                'labors' => $this->response->paginatedCollection($labours->paginate($limit), new UsersTransformer)['data'],
            ]
        ]);
    }

    /**
     * @method  email soft delete
     * @param  [int] $ids [email id]
     * @return [string]     [success message or error message]
     */
    public function delete()
    {
        $input = Request::onlyLegacy('ids', 'force');

        $validator = Validator::make($input, ['ids' => 'required', 'force' => 'boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $this->emailService->deleteMultipleByIds($input['ids'], $input['force']);

            if ($input['force']) {
                $message = trans('response.success.deleted', ['attribute' => 'Email']);
            } else {
                $message = trans('response.success.email_move_to_trash');
            }

            return ApiResponse::success([
                'message' => $message
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * trased email of user
     * @return [json] [trashed emails]
     */
    public function getTrashedEmails()
    {
        try {
            switchDBConnection('mysql2');
            $input = Request::all();
            $input['type'] = Email::TRASHED;
            $emails = $this->emailService->getEmails($input);
            $limit = isset($input['limit']) ? $input['limit'] : \config('jp.pagination_limit');
            if (!$limit) {
                $emails = $emails->get();

                return ApiResponse::success($this->response->collection($emails, new EmailsTransformer));
            }
            $emails = $emails->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($emails, new EmailsTransformer));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * email undo
     * @return [string] [message]
     */
    public function emailRestore()
    {
        $input = Request::onlyLegacy('ids');
        $validator = Validator::make($input, ['ids' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $conversationIds = Email::onlyTrashed()
                ->whereIn('id', (array)$input['ids'])
                ->pluck('conversation_id')->toArray();
            $emails = Email::whereIn('conversation_id', $conversationIds)
                ->withTrashed()
                ->restore();

            return ApiResponse::success([
                'message' => trans('response.success.email_move_to_inbox')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * email print
     * @param  [int] $id [email id]
     * @param  [bool] $recursive [for getting all thread of email]
     * @return [pdf]     [description]
     */
    public function emailPrint($id)
    {
        $input = Request::onlyLegacy('recursive', 'download');
        $email = $this->emailService->getById($id);
        $company = Company::find($this->scope->id());
        if (ine($input, 'recursive')) {
            $email = $email->getThread();
            $emails = $this->response->collection($email, new EmailsTransformer)['data'];
        } else {
            $emails[] = $this->response->item($email, new EmailsTransformer);
        }

        $contents = view('emails.email-print', [
            'emails' => $emails,
            'company' => $company
        ])->render();
        $pdf = PDF::loadHTML($contents)
            ->setPaper('a4')
            ->setOrientation('portrait')
            ->setOption('dpi', 200);
        if (ine($input, 'download')) {
            return $pdf->download('email.pdf');
        }

        return $pdf->stream('email.pdf');
    }

    /**
     * Bounce Email Notification (SNS Notification)
     * @return [type] [description]
     */
    public function bounceEmailNotification()
    {
        try {
            $this->bounceHandler->handleSNSNotifications();

            return ApiResponse::success();
        } catch (\Exception $e) {
            Log::error('Email Bounce handler :' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine());

            return ApiResponse::errorInternal();
        }
    }

    /**
     * applyLabel on the email
     * @return [type] [description]
     */
    public function applyLabel()
    {
        $input = Request::onlyLegacy('thread_ids', 'label_id');

        $validator = Validator::make($input, Email::getApplyLabelRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // Assign Label Id..
        try {
            Email::whereIn('conversation_id', $input['thread_ids'])
                ->update(['label_id' => $input['label_id'], 'is_moved' => true]);

            Firebase::updateUserEmailCount(\Auth::id());

            $labelName = EmailLabel::findOrFail($input['label_id']);

            return ApiResponse::success([
                'message' => 'Email(s) Move to ' . $labelName->name
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * removeLabel from the email
     * @return [type] [description]
     */
    public function removeLabel()
    {
        $input = Request::onlyLegacy('thread_ids');

        $validator = Validator::make($input, ['thread_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // remove labels...

        try {
            Email::whereIn('conversation_id', $input['thread_ids'])
                ->update(['label_id' => null, 'is_moved' => false]);

            Firebase::updateUserEmailCount(\Auth::id());

            return ApiResponse::success([
                'message' => trans('response.success.removed', ['attribute' => 'Label'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
