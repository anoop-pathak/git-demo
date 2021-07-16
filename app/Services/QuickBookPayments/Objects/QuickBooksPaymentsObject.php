<?php

namespace App\Services\QuickBookPayments\Objects;

class QuickBooksPaymentsObject
{
    /**
     * This method will set the payload of the object
     * @param Array $data Data as array to be set as the payload of the object
     * @return Object instance of the object extending this class
     */
    public function setPayload($data)
    {
        foreach ($data as $key => $value) {
            $methodName = 'set' . ucfirst(camel_case($key));
            if(method_exists($this, $methodName)) {
                $this->{$methodName}($value);
            }
        }
        return $this;
    }
	/**
     * This method gets the payload which will be sent to the server
     * @return Array Payload of the object
     */
    public function payload()
    {
        $payload = [];
        foreach ($this->attributes as $attribute) {
            $payload[$attribute] = $this->{$attribute}; 
        }
        return $payload;
    }
	
    /**
     * @return String in Json Format of the payload of the object
     */
    public function payloadAsJson()
    {
        return json_encode($this->payload());
    }
}