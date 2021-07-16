<?php
namespace App\Services\Xactimate\Templates;

use App\Services\Xactimate\BaseXactimate;

class Template26 extends BaseXactimate
{
	protected $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
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

			$textLines = [];
			foreach ($line->childNodes as $key => $childNode) {
				if(isset($childNode->wholeText)) {
					$textLines[$key] = $childNode->wholeText;
				}
			}

			if(!empty($textLines)) {

				if($col == 0 && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
						if(!preg_match('/([0-9]+[a-z]\.+)/', $isBreaked[0])) {
							$data[$row-1][$headers[0]] .= ' '.$textLines[0];
							continue;
						}
					}
				}

				$text = implode(". ", $textLines);

				if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];

				}elseif(in_array($col, [2,5])) {
					if($row == 3 && $col == 5) {
						$data[$row]['description'] .= $text;
					}
					$data[$row][$headers[$col]] = 0;
				}elseif($col == 4) {
					$data[$row][$headers[$col-1]] = $text;
				}else {
					$data[$row][$headers[$col]] = $text;
				}

				$col++;
			}

			if($col == 6){
				$data[$row]['rcv'] = 0;
				$data[$row]['acv'] = 0;
				$col = 0;
				$row++;
			}
		}

		$this->note = implode("\n", array_filter($notes));

		return $data;
	}

	protected function closingTable($class, $line)
	{
		if(in_array($class, ['ft00','ft02'])
			|| ($class == 'ft04' && (strpos($line->textContent, 'Total: ') !== false )
			|| (strpos($line->textContent, 'Totals: ') !== false ))) {
			return true;
		}

		return false;
	}

	protected function startReadingTable($class, $line)
	{
		if($class == 'ft03' && strtolower($line->textContent) == 'total') {
			return true;
		}

		return false;
	}
}
