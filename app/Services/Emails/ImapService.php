<?php namespace App\Services\Emails;

use App\Models\Company;
use App\Models\Email;
use App\Models\EmailAutoRespondTemplate;
use App\Models\Resource;
use App\Models\User;
use App\Repositories\EmailsRepository;
use App\Repositories\ResourcesRepository;
use FlySystem;
use Firebase;
use MobileNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpImap\Mailbox as ImapMailbox;

class ImapService
{

    protected $repo;
    protected $inbox;
    protected $spamBox;
    protected $resourcesRepo;

    public function __construct(EmailsRepository $repo, ResourcesRepository $resourcesRepo)
    {
        $this->repo = $repo;
        $this->resourcesRepo = $resourcesRepo;
        $imap = config('mail.imap');

        // 4. argument is the directory into which attachments are to be saved:
        $filePath = config('flysystem.connections.local.base_path') . '/public/uploads/email_reply_attachments/';

        $this->inbox = new ImapMailbox("{$imap['imap-path']}INBOX", $imap['username'], $imap['password'], $filePath);
        $this->spamBox = new ImapMailbox("{$imap['imap-path']}[Gmail]/Spam", $imap['username'], $imap['password'], $filePath);
    }

    /**
     * Recieve mail from imap server
     * @return [type] [description]
     */
    public function recieveMails()
    {
        // Read Inbox messages:
        $mailsIds = $this->inbox->searchMailbox('UNSEEN');
        $this->fetchAndSaveMail($this->inbox, $mailsIds);

        // Read Spam messages:
        $mailsIds = $this->spamBox->searchMailbox('UNSEEN');
        $this->fetchAndSaveMail($this->spamBox, $mailsIds, false);
    }

    /***************** Private Serction *****************/

    private function fetchAndSaveMail($mailbox, $mailsIds, $inbox = true)
    {
        $imap = config('mail.imap');
        $prefix = $imap['reply-to']['prefix'];

        foreach ($mailsIds as $mailId) {
            $mail = $mailbox->getMail($mailId);
            // var_dump($mail->getAttachments());

            // Get ReplyTo Id ..
            if (strpos($mail->toString, '+' . $prefix) === false) {
                continue;
            }
            $replyToId = explode("+" . $prefix, explode("@", $mail->toString)[0]);
            if (!isset($replyToId[1])) {
                continue;
            }
            $replyToId = (int)$replyToId[1];

            $this->saveReply($mail, $replyToId, $mailId, $inbox);
        }
    }

    /**
     * Save Reply To to an exiting email to our server
     * @param  object $mail | Imap mail object
     * @param  int $replyTo | Email Id (If it is a reply of an existing mail)
     * @param  int $mailboxId | Email mailbox id
     * @return void
     */
    private function saveReply($mail, $replyToId, $mailboxId, $inbox = true)
    {
        // check if already sync
        if (Email::whereMailboxId($mailboxId)->first()) {
            return;
        }

        // check if reply to email exists
        if (!$parentMail = Email::find($replyToId)) {
            return;
        }

        //get company id of reply to mail
        $companyScope = $parentMail->company_id;

        $label_id = $parentMail->label_id;

        $to = [];
        if ($parentMail->fromAddress) {
            $to[] = $parentMail->fromAddress;
        } else {
            if(($createdBy = $parentMail->createdBy) && ($createdBy->email)) {
                $to[] = $createdBy->email;
            }
        }

        //save email replyb in our database

        $meta = [
            'reply_to' => $parentMail->id,
            'mailbox_id' => $mailboxId,
            'stop_transaction' => true,
        ];

        DB::beginTransaction();
        try {
            $message = $this->getMessage($mail);

            setScopeId($companyScope);
			if(isset($parentMail->created_by)) {
				$user  = User::findOrFail($parentMail->created_by);
	 			Auth::login($user);
			}

            $attachments = $this->getAttachments($mail, $companyScope, $meta);

            $repo = App::make(\App\Repositories\EmailsRepository::class);
            $email = $repo->save(
                $type = Email::RECEIVED,
                $mail->subject,
                $message,
                $mail->fromAddress,
                $to,
                $cc = array_keys($mail->cc),
                $bcc = [],
                $attachments,
                $parentMail->created_by,
                $meta
            );
            $email->company_id = $companyScope;
            $email->is_read = false;
            $email->label_id = $label_id;
            $email->save();
        } catch (\Exception $e) {
            DB::rollback();;
            if ($inbox) {
                $this->inbox->markMailAsUnread($mail->id);
            } else {
                $this->spamBox->markMailAsUnread($mail->id);
            }
            Log::error('Email Imap Service:'.$mail->id);
            Log::error($e);

            return false;
            //handle exception
        }
        DB::commit();

        if (isset($parentMail->created_by)) {
            Firebase::updateUserEmailCount($parentMail->created_by);
        }
        $from = $mail->fromName;
        if (!$from) {
            $from = $mail->fromAddress;
        }

        $title = 'New email from ' . $from;

        $message = [];
        if ($email->subject) {
            $message[] = $email->subject;
        }

        if ($body = strip_tags($email->content)) {
            $message[] = substr($body, 0, 100);
        }

        $message = implode(' - ', $message);

        $meta = [
            'thread_id' => $email->conversation_id,
            'email' => $email->from,
            'company_id'	=> $email->company_id,
        ];
        $type = 'new_email';

        MobileNotification::send($email->created_by, $title, $type, $message, $meta);

        $autoRespondTemplate = EmailAutoRespondTemplate::whereUserId($user->id)
            ->whereActive(true)
            ->first();

        if ($autoRespondTemplate) {
            $subject = $autoRespondTemplate->subject;

            $content = $autoRespondTemplate->content;

            $this->autoReplyEmail($subject, $content, $email);
        }
    }

    /**
     * Save Attachments
     * @param  object $mail | Imap mail object
     * @param  int $companyScope | Company Id
     * @return array  | Array of attachments data
     */
    private function getAttachments($mail, $companyScope, $meta = array())
    {
        $attachments = [];
        $mailAttachments = $mail->getAttachments();
        if (empty($mailAttachments)) {
            return $attachments;
        }
        $rootDir = Resource::name(Resource::EMAIL_ATTACHMENTS)
            ->company($companyScope)
            ->first();
        if (!$rootDir) {
            $resourceService = App::make(\App\Services\Resources\ResourceServices::class);
            $root = Resource::companyRoot($companyScope);
            $rootDir = $resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id, false, null, $meta);
        }

        $destination = config('resources.BASE_PATH') . $rootDir->path;

        foreach ($mailAttachments as $key => $attachment) {
            if (empty($attachment->name)) {
                continue;
            }

            // in case of outlook mails..
            if (strpos($attachment->name, 'OUTLOOK.COM.html')) {
                unlink($attachment->filePath);
                continue;
            }

            $filePath = $attachment->filePath;
            $fileName = basename($filePath);

            // get file extension..
            $extension = File::extension($filePath);
            // get file size
            $size = File::size($filePath);
            // get mime type
            $mimeType = getMimeType($filePath);
            // create physical file name..
            $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;

            // move attachments file from local to s3 directory..
            // if(FlySystem::connection('local')->move('public/uploads/email_reply_attachments/'.$fileName, $destination.'/'.$physicalName)){
            $content = FlySystem::connection('local')->readAndDelete('public/uploads/email_reply_attachments/' . $fileName);
            FlySystem::connection('s3')->put($destination . '/' . $physicalName, $content);
            $resource = $this->resourcesRepo->createFile(
                $attachment->name,
                $rootDir,
                $mimeType,
                $size,
                $physicalName,
                null,
                $meta
            );

            $attachments[$key]['value'] = $resource->id;
            $attachments[$key]['type'] = 'attachment';
        }
        return $attachments;
    }

    private function getMessage($mail)
    {
        $message = '';

        $message = $mail->textHtml;

        if (!empty($message)) {
            return $message;
        }

        // in case of outlook mails..
        $attachments = $mail->getAttachments();

        foreach ($attachments as $key => $attachment) {
            if (strpos($attachment->name, 'OUTLOOK.COM.html')) {
                $message = file_get_contents($attachment->filePath);
                break;
            }
        }

        if (!empty($message)) {
            return $message;
        }

        // read text plain;
        return str_replace('\r\n', '<br>', $mail->textPlain);
    }

    private function autoReplyEmail($subject, $content, $email)
    {
        $attachments = [];
        $meta = [];

        if (!$subject) {
            $meta = [
                'reply_to' => $email->id,
            ];

            $subject = $email->subject;
        }


        $createdBy = $email->created_by;
        $emailService = App::make(\App\Services\Emails\EmailServices::class);

        $emailService->sendEmail(
            trim($subject),
            $content,
            (array)$email->from,
            $cc = [],
            $bcc = [],
            (array)$attachments,
            $createdBy,
            $meta
        );
    }
}
