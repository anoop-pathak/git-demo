<?php
namespace App\Services\QuickBooks\Exceptions;

class QuickBookException extends \Exception
{
	protected $faultHandler = null;
    public $context = [];
    private $originalMessage = "";
    public function __construct($message = null, $code = 0, \Exception $previous = null, $faultHandler = null, $context = [])
    {
        if (!$message) {
            throw new $this('Unknown ' . get_class($this));
        }
        $this->originalMessage = $message;
        parent::__construct($message, $code, $previous);
        $this->faultHandler = $faultHandler;
        $this->context = $context;
    }

    public function getFaultHandler()
    {
        return $this->faultHandler;
    }

    public function __toString()
    {
        if($this->faultHandler){
            $this->context['fault_detail'] = $this->faultHandler->getIntuitErrorDetail();
            $this->context['fault_type'] = $this->faultHandler->getIntuitErrorType();
            $this->context['fault_code'] = $this->faultHandler->getIntuitErrorCode();
        }
        $this->message = $this->originalMessage. json_encode($this->context, JSON_HEX_QUOT | JSON_HEX_APOS);
        return parent::__toString();
    }
}