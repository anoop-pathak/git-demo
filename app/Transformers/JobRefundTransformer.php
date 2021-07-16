<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;

class JobRefundTransformer extends TransformerAbstract
{
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
    protected $availableIncludes = ['job', 'financial_account', 'lines'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($refund)
    {
        return [
            'id'               =>  $refund->id,
            'financial_account_id' => $refund->financial_account_id,
            'payment_method'   =>  $refund->payment_method,
            'refund_number'    =>  $refund->refund_number,
            'refund_date'      =>  $refund->refund_date,
            'address'          =>  $refund->address,
            'total_amount'     =>  numberFormat($refund->total_amount),
            'tax_amount'       =>  numberFormat($refund->tax_amount),
            'file_path'        =>  \FlySystem::publicUrl($refund->file_path),
            'canceled_at'      =>  $refund->canceled_at,
            'note'             =>  $refund->note,
            'cancel_note'      =>  $refund->cancel_note,
            'created_at'       =>  $refund->created_at,
            'updated_at'       =>  $refund->updated_at,
            'quickbook_sync_status' => $refund->getQuickbookStatus(),
            'origin'      =>  $refund->originName(),
            'quickbook_id'=>  $refund->quickbook_id,
            'qb_desktop_id' => ''
        ];
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($refund)
    {
        $job = $refund->job;

        if($job) {
            $transformer = (new JobsTransformerOptimized)->setDefaultIncludes([]);

            return $this->item($job, $transformer);
        }
    }

    /* Include finacial_account
     *
     * @return League\Fractal\CollectionResource
     */
    public function includeFinancialAccount($refund)
    {
        $financialAccount = $refund->financialAccount;

        if($financialAccount) {
            return $this->item($financialAccount, new FinancialAccountTransformer);
        }
    }

    /**
     * Include lines
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLines($vendorBills)
    {
        $lines = $vendorBills->lines;

        return $this->collection($lines, new RefundLinesTransformer);
    }
}