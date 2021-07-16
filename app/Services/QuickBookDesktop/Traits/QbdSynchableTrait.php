<?php namespace App\Services\QuickBookDesktop\Traits;

trait QbdSynchableTrait
{
    public function getQBDId()
    {
        return $this->qb_desktop_id;
    }

    public function getEditSequence()
    {
        return $this->qb_desktop_sequence_number;
    }
}