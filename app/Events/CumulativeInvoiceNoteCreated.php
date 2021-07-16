<?php
namespace App\Events;

class CumulativeInvoiceNoteCreated
{
	public $cumulativeInvoiceNote;

	public function __construct( $cumulativeInvoiceNote )
	{
		$this->cumulativeInvoiceNote = $cumulativeInvoiceNote;
	}
}