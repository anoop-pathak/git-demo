<?php
namespace App\Events;

class CumulativeInvoiceNoteUpdated
{
	public $cumulativeInvoiceNote;

	public function __construct( $cumulativeInvoiceNote )
	{
		$this->cumulativeInvoiceNote = $cumulativeInvoiceNote;
	}
}