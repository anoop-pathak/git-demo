<?php
namespace App\Services\Xactimate\Templates;

use App\Services\Xactimate\BaseXactimate;

class Template30 extends BaseXactimate
{
	protected $lines;
	protected $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
	protected $note;

	public function __construct($lines)
	{
		$this->lines = $lines;
	}

	public function get()
	{
		$headers = $this->headers;
		$start = false;
		$data = [];
		$row = 0;
		$col = 0;
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
				if($this->startReadingTable($class, $line, $row)) {
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
				if(in_array($class, ['ft06', 'ft013']) && isset($textLines[0])) {
					$textLines = array_values($textLines);
					$lastKey = count($textLines) - 1;

					if($lastKey > 0) {
						$nextLineDescription = $textLines[$lastKey];
						unset($textLines[$lastKey]);
						$data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

						$isBreaked = explode(' ', $nextLineDescription);
						if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
							if(!preg_match('/([0-9]+[a-z]\.+)/', $isBreaked[0])) {
								$data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
								continue;
							}
						}
						$textLines = [
							$lastKey => $nextLineDescription,
						];

					} else {
						if($col == 0 && isset($textLines[0])) {
							$isBreaked = explode(' ', $textLines[0]);

							if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
								$data[$row-1][$headers[0]] .= ' '.$textLines[0];
								unset($textLines[0]);
							}
						}
					}

					if(!count($textLines)) continue;
				}

				$text = implode(". ", $textLines);

				if((strpos($text, 'NA') !== false)
					|| (strpos($text, '%') !== false)
					|| (strpos($text, 'yrs') !== false)
					|| (strpos($text, 'Avg.') !== false)) {
					continue;
				}

				if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				}else {
					$data[$row][$headers[$col]] = $text;
				}

				$col++;
			}

			if($col == 7){
				$col = 0;
				$row++;
			}
		}

		$this->note = implode("\n", array_filter($notes));

		return $data;
	}

	protected function closingTable($class, $line)
	{
		if($class == 'ft00' || (in_array($class, ['ft03']) && strpos($line->textContent, 'Totals: ') !== false )) {
			return true;
		}

		return false;
	}

	protected function startReadingTable($class, $line, $row)
	{
		if(in_array($class, ['ft09', 'ft03']) && strtolower($line->textContent) == 'acv') {
			return true;
		}

		return false;
	}
}
