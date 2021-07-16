<?php
namespace App\Services\Refunds\Helpers;

use App\Models\JobRefundLine;

class CreateRefundHelper
{
    protected $requestData = [];
    protected $refundLines = [];
    protected $totalAmount = 0;

    protected $jobId;
    protected $customerId;
    protected $financialAccountId;

    public function __construct()
    {
        // ...
    }

    /**
     * Set job id.
     *
     * @param Integer $jobId
     * @return $this
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * get integer job id.
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * Set customer id.
     *
     * @param Integer $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
        return $this;
    }

    /**
     * get integer customer id.
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * Set financial account id.
     *
     * @param Integer $financialAccountId
     * @return $this
     */
    public function setFinancialAccountId($financialAccountId)
    {
        $this->financialAccountId = $financialAccountId;
        return $this;
    }

    /**
     * get integer financial account id.
     */
    public function getFinancialAccountId()
    {
        return $this->financialAccountId;
    }

    /**
     * Set refund lines.
     *
     * @param Array $lines
     * @return $this
     */
    public function setRefundLines($lines = [])
    {
        $this->lines = $lines;
        $this->bindLinesModelObjects();
        $this->calculateTotalRefundAmount();
        return $this;
    }

    /**
     * get list of refund lines
     *
     * @return Collection of RefundLine
     */
    public function getRefundLines()
    {
        return $this->refundLines;
    }

    /**
     * Set refund additional data.
     *
     * @param Array $data
     * @return $this
     */
    public function setAdditionalData($data)
    {
        $this->requestData = $data;
        return $this;
    }

    /**
     * check and get payment method from the request data.
     */
    public function getPaymentMethod()
    {
        return isSetNotEmpty($this->requestData, 'payment_method') ?: null;
    }

    /**
     * check and get origin from the request data.
     */
    public function getOrigin()
    {
        return isSetNotEmpty($this->requestData, 'origin') ?: 0;
    }

    /**
     * check and get refund number from the request data.
     */
    public function getRefundNumber()
    {
        return isSetNotEmpty($this->requestData, 'refund_number') ?: null;
    }

    /**
     * check and get refund date from the request data.
     */
    public function getRefundDate()
    {
        return isSetNotEmpty($this->requestData, 'refund_date') ?: null;
    }

    /**
     * check and get address from the request data.
     */
    public function getAddress()
    {
        return isSetNotEmpty($this->requestData, 'address') ?: null;
    }

    /**
     * check and get tax amount from the request data.
     */
    public function getTaxAmount()
    {
        return isSetNotEmpty($this->requestData, 'tax_amount') ?: null;
    }

    /**
     * check and get note from the request data.
     */
    public function getNote()
    {
        return isSetNotEmpty($this->requestData, 'note') ?: null;
    }

    /**
     * get total amount which is calculated by refund lines (quantity * rate).
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Get additional request data.
     */
    public function getData()
    {
        return $this->requestData;
    }

    /**
     * convert array job refund lines to Model instance.
     *
     * @return $this
     */
    private function bindLinesModelObjects()
    {
        $lines = $this->lines;
        $refundLines = [];
		foreach ($lines as $line) {
			$refundLines[] = new JobRefundLine($line);
		}

        $this->refundLines = $refundLines;
		return $this;
    }

    /**
     * Calculate refund amount on the basis of refund lines.
     *
     * @return $this;
     */
    private function calculateTotalRefundAmount()
    {
        $totalAmount = 0;
        if(!$this->refundLines) {
            $this->totalAmount = $totalAmount;
            return $this;
        }
        $totalAmount = 0;
        $lines = $this->refundLines;
        foreach ($lines as $line) {
			$lineAmount = $line->rate *  $line->quantity;
			$totalAmount += $lineAmount;
        }
        $this->totalAmount = $totalAmount;
        return $this;
    }
}