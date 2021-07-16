<?php 
namespace App\Presenters;
use Laracasts\Presenter\Presenter;
class SrsShipToAddressPresenter extends Presenter
{
	/**
	 * Full address
	 * @return [string] [address]
	 */
	public function fullAddress()
	{
		$address = [$this->address_line1];
 		$additionalAddress = [$this->address_line2, $this->address_line3];
 		$address[] 	 = implode(', ', array_filter($additionalAddress));
		$firstLine 	 = implode(',<br>', array_filter($address));
		$address2 	 = [$this->city, $this->state, $this->zip_code];
		$secondLine  = implode(", ", array_filter($address2));
		$fullAddress = [$firstLine, $secondLine];
		$fullAddress = implode(",<br>", array_filter($fullAddress));
 		return $fullAddress;
	}
 	public function fullAddressOneLine()
	{
		$address = $this->fullAddress();
 		return str_replace('<br>', ' ', $address);
	}
}