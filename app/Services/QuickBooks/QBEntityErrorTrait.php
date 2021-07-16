<?php
namespace App\Services\QuickBooks;
use App\Models\QBEntityError;

trait QBEntityErrorTrait {

    public function SaveEntityErrorLog($entity, $entityId, $errorCode, $message, $details = null, $errorType = null, $meta = [])
    {
    	$entityError = new QBEntityError;
    	$entityError->company_id = getScopeId();
    	$entityError->entity_id = $entityId;
    	$entityError->entity = $entity;
    	$entityError->error_code = $errorCode;
    	$entityError->message = $message;
    	$entityError->error_type = $errorType;
    	$entityError->details = $details;
    	$entityError->meta = json_encode($meta);
    	$entityError->save();

    }
}