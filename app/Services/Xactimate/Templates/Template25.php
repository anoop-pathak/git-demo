<?php
namespace App\Services\Xactimate\Templates;

use App\Services\Xactimate\BaseXactimate;

class Template25 extends BaseXactimate
{
	protected $headers = ['description', 'quantity_unit', 'price', 'tax', 'test', 'rcv', 'depreciation', 'acv'];

	protected $lines;

	protected $note;

	public function __construct($lines)
	{
		$this->lines = $lines;
	}

	public function get()
	{
		$start = false;
		$data  = [];
		$headers = $this->headers;
		$row   = 0;
		$col   = 0;
		$notes = [];

		foreach ($this->lines as $key => $line) {
			$class = null;
			$textLines = [];

			if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}

			# end of table
			if($this->closingTable($class, $line)) {
				$start = false;
				continue;
			}

			if(!$start) {
				if($this->startReadingTable($class, $line)) {
					$start = true;
				}

				continue;
			}

			foreach ($line->childNodes as $key => $childNode) {
				if(isset($childNode->wholeText)) {
					$textLines[$key] = $childNode->wholeText;
				}
			}

			if(!empty($textLines)) {

				if(in_array($class, ['ft011', 'ft05'])
					&& (strpos($line->textContent, 'composition shingles') !== false)
					|| (strpos($line->textContent, 'Line set cover.') !== false)
					|| (strpos($line->textContent, 'As incurred.') !== false)
					|| (strpos($line->textContent, 'Allows for replacing the ') !== false)) {

					$data[$row-1][$headers[0]] .= ' '.$textLines[0];
					unset($textLines[0]);
					continue;
				}

				if($col == 0 && $row == 2 && $class == 'ft013') {
					$data[$row-1][$headers[0]] .= ' '.$textLines[0];
					unset($textLines[0]);
					continue;
				}

				if(in_array($class, ['ft013', 'ft07']) && isset($textLines[0])) {
					$textLines = array_values($textLines);
					$lastKey = count($textLines) - 1;

					if($lastKey > 0) {
						$nextLineDescription = $textLines[$lastKey];
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];

						unset($textLines);
						$textLines = [
							0 => $nextLineDescription,
						];

					} else {
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];
						unset($textLines[0]);
					}

					if(!count($textLines)) continue;
				}

				$text = implode(". ", $textLines);

				if($col == 2) {
					if($textLines[0] == 'OPEN ITEM') {

						$data[$row]['price'] = 0.0;
						$data[$row]['tax'] = 0.0;
						$data[$row]['rcv'] = 0.0;
						$data[$row]['depreciation'] = 0;
						$data[$row]['acv'] = 0.0;

						$col = 0;
						$row++;

						continue;
					}
				}

				if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];

				} elseif ($col == 4) {
					$col++;
					continue;

				} else {
					$data[$row][$headers[$col]] = $text;
				}

				$col++;
			}

			if($col == 8){
				$col = 0;
				$row++;
			}
		}

		$this->note = implode("\n", array_filter($notes));

		return $data;
	}

	protected function closingTable($class, $line)
	{
		if($class == 'ft00'
			|| (in_array($class,['ft010', 'ft04'])
				&& strpos($line->textContent, 'Totals:') !== false)) {
			return true;
		}

		return false;
	}

	protected function startReadingTable($class, $line)
	{
		if(in_array($class, ['ft010', 'ft04'])
			&& strtolower($line->textContent) == 'acv') {

			return true;
		}

		return false;
	}
}
