<?php namespace App\Services\QuickBooks;

trait QboSynchableTrait
{
    public function getQBOId(){
        return $this->quickbook_id;
    }

    public function getLogDisplayName(){
        return $this->id;
    }

    public function getCustomerId(){
        return $this->customer_id;
    }

}