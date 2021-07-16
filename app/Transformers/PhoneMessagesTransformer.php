<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\CustomersTransformer;
use App\Transformers\MessageThreadTransformer;

class PhoneMessagesTransformer extends TransformerAbstract {

	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['media', 'customer', 'sender', 'thread'];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($message) {
		return [
			'id'             => $message->id,
            'sender_id'      => $message->sender_id,
            'company_id'     => $message->company_id,
            'subject'        => $message->subject,
            'content'        => $message->content,
            'sms_id'         => $message->sms_id,
            'sms_status'     => $message->sms_status,
            'customer_id'    => $message->customer_id,
            'thread_id'      => $message->thread_id,
            'created_at'     => $message->created_at,
		];
	}

    public function includeMedia($message)
    {
        $media = $message->media;
        if(!$media->isEmpty()) {
            return $this->collection($media, function($media){
                return [
                    'id'                => $media->id,
                    'message_sid'       => $media->sid,
                    'media_url'         => $media->media_url,
                    'short_url'         => $media->short_url,
                ];
            });
        }
    }

    public function includeCustomer($message)
    {
        $customer = $message->customer;
        if($customer) {
            $transformer = (new CustomersTransformer)->setDefaultIncludes([]);
            return $this->item($customer, $transformer);
        }
    }

    /**
     * Include Thread
     * @param  Instance $message Message
     * @return Thread
     */
    public function includeThread($message)
    {
        $thread = $message->thread;
        if($thread) {
            $thread->thread_id = $message->thread_id;
            return $this->item($thread, new MessageThreadTransformer);
        }
    }

    public function includeSender($message)
    {
        $sender = $message->sender;

        if($sender) {
            return $this->item($sender, function($sender){
                return [
                    'id'         => $sender->id,
                    'first_name' => $sender->first_name,
                    'last_name'  => $sender->last_name,
                    'full_name'  => $sender->full_name,
                    'full_name_mobile'  => $sender->full_name_mobile,
                    'profile_pic'       => $sender->getUserProfilePic(),
                ];
            });
        }
    }
}