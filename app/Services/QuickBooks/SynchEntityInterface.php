<?php namespace App\Services\QuickBooks;

Interface SynchEntityInterface{

    public function getQBOId();
    public function getLogDisplayName();
    public function getCustomerId();
}