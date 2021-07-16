<?php
namespace App\Services\Xactimate\Templates;

use App\Services\Xactimate\BaseXactimate;

class Template27 extends BaseXactimate
{
	protected $lines;
	protected $headers = ['description', 'quantity_unit', 'price', 'tax', 'test', 'rcv', 'depreciation', 'acv'];
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
			if($class == 'ft04') {
				foreach ($line->childNodes as $key => $childNode) {
					$textLines[$key] = $childNode->textContent;
				}
			}else {
				foreach ($line->childNodes as $key => $childNode) {
					if(isset($childNode->wholeText)) {
						$textLines[$key] = $childNode->wholeText;
					}
				}
			}

			if(!empty($textLines)) {
				if($col == 2) {
					if($textLines[0] == '<REVISED>') {
						$data[$row]['price'] = 0;
						$data[$row]['tax'] = 0;
						$data[$row]['rcv'] = 0;
						$data[$row]['depreciation'] = 0;
						$data[$row]['acv'] = 0;

						$col = 0;
						$row++;

						continue;
					}
				}

				if(in_array($class, ['ft04']) && $col == 0 && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
						if(!preg_match('/([0-9]+[a-z]\.+)/', $isBreaked[0])) {
							$data[$row-1][$headers[0]] .= ' '.$textLines[0];
							continue;
						}
					}
				}

				if(in_array($class, ['ft021', 'ft06', 'ft09', 'ft08', 'ft010', 'ft05']) && isset($textLines[0])) {
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

				if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				} elseif ($col == 4) {
					$col++;
					continue;
				}else {
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
		if(in_array($class, ['ft00','ft02'])
			|| ($class == 'ft04' && (strpos($line->textContent, 'Total: ') !== false )
			|| (strpos($line->textContent, 'Totals: ') !== false ))) {
			return true;
		}

		return false;
	}

	protected function startReadingTable($class, $line)
	{
		if(in_array($class, ['ft03','ft05'])
			&& strtolower($line->textContent) == 'acv') {

			return true;
		}

		return false;
	}
}
