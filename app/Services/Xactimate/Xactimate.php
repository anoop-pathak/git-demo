<?php namespace App\Services\Xactimate;

use App\Models\ApiResponse;
use DOMDocument;
use File;
use FlySystem;
use App\Services\Xactimate\Templates\Template25;
use App\Services\Xactimate\Templates\Template26;
use App\Services\Xactimate\Templates\Template27;
use App\Services\Xactimate\Templates\Template30;

class Xactimate
{
    public $bin;
    public $note;
    public $templateHeaders;

    # CRUZ_ARMANDO, HO_ROLAND_Final Draft with_without Removal Depreciation,
    # POEI_FRANK_Final Draft with_without Removal Depreciation
    # public_uploads_estimations_15837875425e66ae16b5b23_adam_ibrahim_insurance.pdf
    protected $template1 = ['ft04', 'description', 'quantity unit price', 'tax', 'rcv', 'deprec.', 'acv'];
    # for new files..
    # KEZEKIA MUKASA, Allstate
    protected $template2 = ['ft010', 'description', 'quantity', 'unit', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];
    # Loudermilk Scope
    protected $template3 = ['ft08', 'description', 'quantity', 'unit', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];
    # LOUDERMILK_CHRISTINA_Final Draft, down.pdf
    protected $template4 = ['ft04', 'description', 'quantity unit price', 'tax', 'o&p', 'rcv', 'deprec.', 'acv'];
    # bowles x scope
    protected $template5 = ['ft03', 'description', 'qty'];
    # JMAC bowles estimate, JMAC brazos est2, Sharp_Kristi_ShedLineItemDetail
    protected $template6 = ['ft04', 'description', 'qty', 'remove', 'replace', 'tax', 'o&p', 'total'];
    # ENGLE_ROB new file..
    protected $template7 = ['ft014', 'description', 'quantity unit price', 'tax', 'rcv', 'deprec.', 'acv'];
    # TESTXACTIMATE
    protected $template8 = ['ft03', 'description', 'quantity unit price', 'tax', 'rcv', 'deprec.', 'acv'];
    # State Farm
    protected $template9 = ['ft02', 'description', 'quantity', 'unit price', 'tax', 'gco&p', 'rcv'];
    # heartland test, State Farm Roof Claim -  Turnipseed
    protected $template10 = ['ft03', 'quantity', 'unit price', 'tax', 'rcv', 'age/life', 'deprec.', 'acv'];
    # ferg xact, Cherry from One Oak, Jimmy Idoski Final Ins.pdf
    protected $template11 = ['ft04', 'quantity', 'unit', 'tax', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];
    # TEXSTAR925_Final Draft
    protected $template12 = ['ft04', 'description', 'qty', 'remove', 'replace', 'tax', 'total'];
    # Flamm_25-8004-K53_FINAL_DRAFT_76, JMAC est canonico 1
    protected $template13 = ['ft04', 'description', 'qty', 'reset', 'remove', 'replace', 'tax', 'o&p', 'total'];
    # Andersson insurance 1, ISOW 22 S Pearson Keller (1).pdf
	# WESTWOOD_TOWNHOME_OW_INSURED_COPY_68 (002).pdf
	# 15877546175ea33679d3b19_Ford, John & Kristin - Horse Barn estimate.pdf
    protected $template14 = ['ft03', 'description', 'qty unit price', 'tax', 'rcv', 'deprec.', 'acv'];
    # BRET_JOHNSON_FIANL_DRAFT
    protected $template15 = ['ft02', 'description', 'qty', 'unit price', 'total'];
    # Farmers
    protected $template16 = ['ft02', 'quantity', 'unit', 'tax', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];
    # James Rice
    protected $template17 = ['ft010', 'description', 'quantity unit price', 'tax', 'rcv', 'deprec.', 'acv'];
    # 4328168233_Bush
    protected $template18 = ['ft04', 'description', 'qty', 'reset', 'remove', 'replace', 'tax', 'total'];
    # Puckett Insurance Estimate, Sarah Puckett
    protected $template19 = ['ft04', 'description', 'qty', 'unit price', 'amount', 'additional *', 'cost value', 'depreciation', 'value'];

    # Garrison_Gary_USAA_Est2, USAA Roof EstimateDocument.1.pdf
    protected $template20 = ['ft03', 'description', 'quantity', 'unit price', 'rcv', 'depreciation', 'acv'];

    # CHRISTINE_ARAZAN_Final Draft with_without Removal Depreciation
    protected $template21 = ['ft04', 'description', 'quantity', 'unit', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];

    # 15742859525dd5b28041942_aaa est
    protected $template22 = ['ft08', 'quantity', 'unit', 'tax', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];

    # Final Draft-Karen Carrington
    protected $template23 = ['ft03', 'description', 'qty', 'remove', 'replace', 'tax', 'o&p', 'total'];

    # ISOW Smith 4912 Pack Saddle Way FM.pdf
	protected $template24 = ['ft08', 'description', 'quantity unit price', 'tax', 'rcv', 'deprec.', 'acv'];

	# #3364392 - 420 PARK-REPAIR ESTIMATE WITH DEPRECIATION
	protected $template25 = ['ft010', 'description', 'quantity unit price', 'tax', 'o&p', 'rcv', 'deprec.', 'acv'];

	# LHP-MR_Final Draft.pdf
	protected $template26 = ['ft03', 'description', 'qty', 'remove', 'replace', 'tax', 'total'];

	# FINAL_DRAFT_DEPREC_CAR.pdf, mayberry appraised final.pdf
	protected $template27 = ['ft03', 'description', 'quantity unit price', 'tax', 'o&p', 'rcv', 'deprec.', 'acv'];

	#public_uploads_estimations_15932217865ef6a29ad1dcc_chris_martinsek_insurance_estimate.pdf
	protected $template30 = ['ft09', 'quantity', 'unit', 'tax', 'rcv', 'age/life', 'cond.', 'dep %', 'deprec.', 'acv'];

    public function parsePdf($file)
    {
        $tempPath = 'temp/'.rand().'.pdf';
        FlySystem::writeStream($tempPath, $file);
        $binaryPath = "/usr/bin/";
        $full_settings = [
            'pdftohtml_path' => "{$binaryPath}pdftohtml",
            'pdfinfo_path' => "{$binaryPath}pdfinfo",
            'generate' => [ // settings for generating html
                'ignoreImages' => true, // we need images
                'singlePage' => false, // we want separate pages
                'imageJpeg' => false, // we want png image
                // 'zoom' => 1.5, // scale pdf
                'noFrames' => false, // we want separate pages
            ],
            'clearAfter' => true, // auto clear output dir (if removeOutputDir==false then output dir will remain)
            'removeOutputDir' => true, // remove output dir
            'outputDir' => 'temp/'.uniqid(), // output dir
            'html' => [ // settings for processing html
                'inlineCss' => false, // replaces css classes to inline css rules
                'inlineImages' => true, // looks for images in html and replaces the src attribute to base64 hash
                'onlyContent' => true, // takes from html body content only
            ]
        ];
        $pdf = new \TonchikTm\PdfToHtml\Pdf($file, $full_settings);
        if(empty($pdf->getInfo()) || $pdf->countPages() < 2) {
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'Insurance file']));
        }
        $pages = $pdf->getHtml()->getAllPages();
        $data['first_page'] = $this->extractFirstPageData($pages[1]);
        // unset($pages[1]);

        $data['table_data'] = $this->extractTabelData($pages);
        $data['meta']['notes'] = $this->note;

        $data['meta']['xactimate_file'] = $this->uploadFile($tempPath);
        if (!isset($data['table_data'][0])
            || !isset($data['table_data'][0]['rcv'])
            || !isset($data['table_data'][0]['acv'])
            || !isset($data['table_data'][0]['depreciation'])
            || !isset($data['table_data'][0]['tax'])
            || !isset($data['table_data'][0]['description'])
            || !isset($data['table_data'][0]['quantity'])
            || !isset($data['table_data'][0]['unit'])
            || !isset($data['table_data'][0]['price'])) {
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'Insurance file']) );
        }

        FlySystem::delete($tempPath);

        foreach ($data['table_data'] as $key => $value) {
            if($value['price']) {
                $data['table_data'][$key]['price'] = parseIntFloatValues($value['price']);
            }
            if($value['tax']) {
                $data['table_data'][$key]['tax'] = parseIntFloatValues($value['tax']);
            }
            if($value['rcv']) {
                $data['table_data'][$key]['rcv'] = parseIntFloatValues($value['rcv']);
            }
            if($value['depreciation']) {
                $data['table_data'][$key]['depreciation'] = parseIntFloatValues($value['depreciation']);
            }
            if($value['acv']) {
                $data['table_data'][$key]['acv'] = parseIntFloatValues($value['acv']);
            }
            if($value['quantity']) {
                $data['table_data'][$key]['quantity'] = parseIntFloatValues($value['quantity']);
            }
        }
        return $data;
    }
    private function convertDataToArray($htmlContent)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        // $dom->loadHTML(mb_convert_encoding(str_replace(' ', ' ', $htmlContent), 'HTML-ENTITIES', 'UTF-8'));
        $dom->loadHTML(str_replace(' ', ' ', $htmlContent));
        return explode("\n", $dom->childNodes->item(1)->nodeValue);
    }
    private function extractFirstPageData($data) 
    {
        $data = $this->convertDataToArray($data);
        $ret = [];
        foreach ($data as $key => $value) {
            $data[$key] = trim($value); //
        }
        $key = array_search('Insured:', $data);
        $ret['insured'] = $data[++$key];
        $claimNumber  = preg_grep ('/^Claim Number: (\w+)/i', $data);
        if(!empty($claimNumber)){
            $claimNumber = explode(':', reset($claimNumber))[1];
        }else{
            $claimNumber = "";
        }
        $ret['claim_number'] = preg_replace("/[^a-zA-Z0-9]+/", "", $claimNumber);
        $policyNumber  = preg_grep ('/^Policy Number: (\w+)/i', $data);
        if(!empty($policyNumber)){
            $policyNumber = explode(':', reset($policyNumber))[1];
        }else{
            $policyNumber = "";
        }
        $ret['policy_number'] = preg_replace("/[^a-zA-Z0-9]+/", "", $policyNumber);
        return $ret;
    }
    private function extractTabelData($pages)
    {
        $content = "";
        foreach ($pages as $page) {
            $content .= $page;
        }
        $content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', ' ', $content);
        $dom = new DOMDocument;
        $dom->loadHTML(str_replace(' ', ' ', $content));
        $lines = $dom->getElementsByTagName('p');
        $this->templateHeaders = $this->getTemplateHeaders($lines);
        switch ($this->templateHeaders) {
            case $this->template1:
                $data = $this->getFirstTemplateData($lines);
                break;
            case $this->template2:
                $data = $this->getSecondTemplateData($lines);
                break;
            case $this->template3:
                $data = $this->getThirdTemplateData($lines);
                break;
            case $this->template4:
                $data = $this->getFourthTemplateData($lines);
                break;
            case $this->template5:
                $data = $this->getFifthTemplateData($lines);
                break;
            case $this->template6:
                $data = $this->getSixthTemplateData($lines);
                break;
            case $this->template7:
                $data = $this->getTemplate7($lines);
                break;
            case $this->template8:
                $data = $this->getTemplate8($lines);
                break;
            case $this->template9:
                $data = $this->getTemplate9($lines);
                break;
            case $this->template10:
                $data = $this->getTemplate10($lines);
                break;
            case $this->template11:
                $data = $this->getTemplate11($lines);
                break;
            case $this->template12:
                $data = $this->getTemplate12($lines);
                break;
            case $this->template13:
                $data = $this->getTemplate13($lines);
                break;
            case $this->template14:
                $data = $this->getTemplate14($lines);
                break;
            case $this->template15:
                $data = $this->getTemplate15($lines);
                break;
            case $this->template16:
                $data = $this->getTemplate16($lines);
                break;
            case $this->template17:
                $data = $this->getTemplate17($lines);
                break;
            case $this->template18:
                $data = $this->getTemplate18($lines);
                break;
            case $this->template19:
                $data = $this->getTemplate19($lines);
                break;
            case $this->template20:
				$data = $this->getTemplate20($lines);
                break;
            case $this->template21:
                $data = $this->getTemplate21($lines);
                break;
            case $this->template22:
                $data = $this->getTemplate22($lines);
                break;
            case $this->template23:
                $data = $this->getTemplate23($lines);
                break;
            case $this->template24:
                $data = $this->getTemplate24($lines);
                break;
            case $this->template25:
                $template = new Template25($lines);
                $data = $template->get();
                $this->note = $template->getNote();
                break;
            case $this->template26:
                $template = new Template26($lines);
                $data = $template->get();
                $this->note = $template->getNote();
                break;
            case $this->template27:
                $template = new Template27($lines);
                $data = $template->get();
                $this->note = $template->getNote();
                break;
            case $this->template30;
                $template = new Template30($lines);
                $data = $template->get();
                $this->note = $template->getNote();
                break;
            default:
                $data = [];
                break;
        }
        return $data;
    }
    /**
     * upload temporary file
     * @param  $file
     * @return $path
     */
    private function uploadFile($filePath)
    {
        $name = uniqueTimestamp().'_xactimate.pdf';
        FlySystem::copy($filePath, 'temp/'.$name);

        return $name;
    }
    private function getTemplateHeaders($lines)
    {
        $headers = [];
        $start = false;
        $skipRcv = false;
        $checkReplace = false;

        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            if(!$start) {
                if(in_array($class,['ft010', 'ft08', 'ft04', 'ft03', 'ft014', 'ft02'])
                     && strtolower($line->textContent) == 'description') {
                    $start = true;
                    $headers[] = $class;
                }elseif (in_array($class, ['ft03', 'ft04', 'ft02', 'ft08', 'ft09']) && strtolower($line->textContent) == 'quantity') {

                    if($class == 'ft02') {
                        $skipRcv = true;
                    }

                    $start = true;
                    $headers[] = $class;
                }
            }
            if($start) {
                $nextColumn = explode('. ', $line->textContent);
				if($checkReplace && in_array('qty', $headers) && is_numeric($nextColumn[0]) && $class == 'ft03') {

					break;
                }

                $headers[] = strtolower($line->textContent);

                if(strtolower($line->textContent) == 'acv'
					|| (strtolower($line->textContent) == 'qty' && $class == 'ft03')
                    || (strtolower($line->textContent) == 'total' && $class == 'ft04')
                    || (strtolower($line->textContent) == 'total' && $class == 'ft03')
					|| (strtolower($line->textContent) == 'value' && $class == 'ft04')) {
                        if(strtolower($line->textContent) == 'qty' && $class == 'ft03') {
                            $checkReplace = true;
                            continue;
                        }
					break;
				}elseif(!$skipRcv && $class == 'ft02' && (in_array(strtolower($line->textContent), ['rcv', 'total']))) {
					break;
				}
            }
        }
        return $headers;
    }

    private function getFirstTemplateData($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            # end of table
            if($class == 'ft00' || ($class == 'ft04' && (strpos($line->textContent, 'Total:') !== false || strpos($line->textContent, 'Totals:') !== false))) {
                $start = false;
                continue;
            }
            if(!$start) {
                if($class == 'ft04' && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }

                continue;
            }

            # skip bold headings..
            if(in_array($class, ['ft011', 'ft04'])) {
                continue;
            }
            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }
            if(!empty($textLines)) {
                # append breaked description to previous row
                if($class == 'ft08' && isset($textLines[0])) {
                    $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                    unset($textLines[0]);
                    if(!count($textLines)) continue;
                }

                if(in_array($class, ['ft06', 'ft09', 'ft010']) && isset($textLines[0])) {
                    $textLines = array_values($textLines);
                    $lastKey = count($textLines) - 1;

                    if($lastKey > 0) {
                        $nextLineDescription = $textLines[$lastKey];
                        unset($textLines[$lastKey]);
                        $data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

                        $isBreaked = explode(' ', $nextLineDescription);
                        if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                            $data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
                            continue;
                        }
                        $textLines = [
                            $lastKey => $nextLineDescription,
                        ];

                    } else {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines[0]);
                    }

                    if(!count($textLines)) continue;
                }

                # append breaked content to previous line with same class
				if($col == 0 && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(isset($isBreaked[0]) && (!preg_match('/([0-9]+\.  +)/', $isBreaked[0].'  '))) {
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];
						unset($textLines);
						continue;
					}
				}

                $text = implode(". ", $textLines);

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

    private function getSecondTemplateData($lines)
    {
        $start = false;
        $data  = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row   = 0;
        $col   = 0;
        $notes = [];

        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            // check end of table/page
            if($class == 'ft00' || (in_array($class, ['ft010', 'ft04']) && strpos($line->textContent, 'Totals:') !== false)) {

                // concat breaked description to previous row and unset incompleted row
                if(isset($data[$row]['description']) && !isset($data[$row]['quantity'])) {
                    $data[$row-1][$headers[0]] .= ' '.$data[$row]['description'];
                    unset($data[$row]);
                    $col = 0;
                }

                $start = false;
            }

            if(!$start) {
                if(in_array($class, ['ft010', 'ft04']) && strtolower($line->textContent) == 'acv') {
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

                # append breaked content of previous line with <br> tag
                if(in_array($class, ['ft013', 'ft09']) && isset($textLines[0])) {
                    $textLines = array_values($textLines);
                    $lastKey = count($textLines) - 1;

                    if($lastKey > 0) {
                        $nextLineDescription = $textLines[$lastKey];
                        unset($textLines[$lastKey]);
                        $data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

                        $isBreaked = explode(' ', $nextLineDescription);
                        if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                            $data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
                            continue;
                        }
                        $textLines = [
                            $lastKey => $nextLineDescription,
                        ];

                    } else {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines[0]);
                    }

                    if(!count($textLines)) continue;
                }

                # append breaked content to previous line with same class
                if($col == 0 && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && (!preg_match('/([0-9]+\.  +)/', $isBreaked[0].'  '))) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines);
                        continue;
                    }
                }

                $text = implode(". ", $textLines);

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif($col == 3) {
                        $data[$row][$headers[$col]] = '';
                        $col++;
                        $data[$row][$headers[$col]] = $text;
                }elseif($col == 7) {
                    $key = $col - 2;
                    $data[$row][$headers[$key]] = $text;
                }elseif($col == 8) {
                    $key = $col - 2;
                    $data[$row][$headers[$key]] = $text;
                }else {
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }

            if($col == 9){
                $col = 0;
                $row++;
            }
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getThirdTemplateData($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];

        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            if(!$start) {
                if($class == 'ft08' && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }

                continue;
            }
            // end of table
            if($class == 'ft08' && strpos($line->textContent, 'Totals:') !== false) {

                break;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }

            if(!empty($textLines)) {
                if($class == 'ft011' && isset($textLines[0])) {
                    $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                    unset($textLines[0]);
                    if(!count($textLines)) continue;
                }

                $text = implode(". ", $textLines);

                // if($class == 'ft06') {

                //  $data[$row-1][$headers[0]] .= ' '.$text;

                //  continue;
                // }

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif($col == 3) {
                        $data[$row][$headers[$col]] = '';
                        $col++;
                        $data[$row][$headers[$col]] = $text;
                }elseif ($col == 7) {
                    $key = $col - 2;
                    $data[$row][$headers[$key]] = $text;
                }elseif ($col == 8) {
                    $key = $col - 2;
                    $data[$row][$headers[$key]] = $text;
                }else {
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }

            if($col == 9){
                $col = 0;
                $row++;
            }
        }

        // append breaked description to last row
        if(isset($data[$row]['description']) && !isset($data[$row]['quantity'])) {
            $data[$row-1][$headers[0]] .= ' '.$data[$row]['description'];
            unset($data[$row]);
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getFourthTemplateData($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'test', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            // end of table/page
            if($class == 'ft00' || ($class == 'ft04' && ((strpos($line->textContent, 'Total:') || strpos($line->textContent, 'Totals:')) !== false))) {
                $start = false;
            }

            if(!$start) {
                if($class == 'ft04' && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }

                continue;
            }

            // get extra notes
            if (in_array($class, ['ft08'])) {
                foreach ($line->childNodes as $key => $childNode) {
                    // skip urls
                    if($childNode->textContent == 'English_Spanish.pdf'
                        || substr($childNode->textContent, 0, 5) == 'https') {
                        continue;
                    }
                    $notes[] = $childNode->textContent;
                }
            }

            // skip bold headings..
            if($class == 'ft08') {
                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }

            if(!empty($textLines)) {

                if(($class == 'ft07' || $class == 'ft06') && isset($textLines[0])) {

                    $textLines = array_values($textLines);
                    $lastKey = count($textLines) - 1;
                    $nextLineDescription = $textLines[$lastKey];

                    if($lastKey > 0) {
                        unset($textLines[$lastKey]);

                        // remove rows with invalid content like ('_______', urls)
                        $newTextLines = [];
                        foreach ($textLines as $key => $textLine) {
                            if(preg_match("/[a-z0-9]/i", $textLine)
                                && substr($textLine, 0, 5) != 'https'
                                && $textLine != 'English_Spanish.pdf'){
                                $newTextLines[$key] = $textLine;
                            }
                        }

                        $textLines = $newTextLines;
                        // append breaked content to previous row
                        $data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

                        // set next line description
                        $textLines = [
                            0 => $nextLineDescription,
                        ];
                    }else {
                        // append breaked content to previous row
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines[0]);
                    }
                }

                // append breaked description in case of same class
                if($col == 0 && isset($textLines[0])) {

                    $isBreaked = explode(' ', $textLines[0]);
                    // skip incorrect lines (like 39.40)
                    if(preg_match('/([0-9]+\.[0-9a-zA-Z]+)/', $isBreaked[0])) {
                        continue;
                    }

                    // append breaked content to previous line
                    if(isset($isBreaked[0]) && (!preg_match('/([0-9]+\.  +)/', $isBreaked[0].'  '))) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines);
                        continue;
                    }
                }

                if(!count($textLines)) continue;

                $text = implode(". ", $textLines);

                if($col == 1) {
                    if($row == 3 && strpos($data[$row]['description'], '1,895.59 SQ')) {
                        $data[$row][$headers[$col + 1]] = $text;
                        $text = substr($data[$row]['description'], strpos($data[$row]['description'], '1,895.59 SQ'));
                        $data[$row]['description'] = substr($data[$row]['description'], 0, strpos($data[$row]['description'], '1,895.59 SQ'));
                        $col++;
                    }
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                // skip extra column (O&P)
                }elseif($col == 4) {
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

    private function getFifthTemplateData($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];

        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            // check end of page/table
            if($class == 'ft00' 
                ||($class == 'ft02' && strpos($line->textContent, 'NOTES:') !== false)) {
                $start = false;
            }

            if(!$start) {
                if($class == 'ft03' && strtolower($line->textContent) == 'qty') {
                    $start = true;
                }

                continue;
            }

            // get extra bold content (notes)
            if (in_array($class, ['ft04', 'ft06'])) {

                foreach ($line->childNodes as $key => $childNode) {

                    if (isset($childNode->textContent)) {
                        $notes[] = $childNode->textContent;
                    }
                }
            }

            // skip bold headings..
            if($class == 'ft04' || $class == 'ft06' || $class == 'ft05') {
                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->textContent)) {
                    $textLines[$key] = $childNode->textContent;
                }
            }

            if(!empty($textLines)) {
                $text = implode(". ", $textLines);

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }else {
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }

            if($col == 2){
                $data[$row]['price'] = '';
                $data[$row]['tax'] = '';
                $data[$row]['rcv'] = '';
                $data[$row]['depreciation'] = '';
                $data[$row]['acv'] = '';

                $col = 0;
                $row++;
            }
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getSixthTemplateData($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        $colPos = 0;
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            // check end of page/table & set (start = false)
            if($class == 'ft00' || (in_array($class, ['ft06', 'ft05', 'ft03']) && (strpos($line->textContent, 'Totals:') !== false || strpos($line->textContent, 'Total:') !== false))) {
                // unset incompleted row
                if(isset($data[$row]) && count($data[$row] != 8)) unset($data[$row]);
                $start = false;
                $colPos = 0;
            }

            if(!$start) {
                if($class == 'ft04' && strtolower($line->textContent) == 'description') {
                    $start = true;
                    $colPos++;
                }

                continue;
            }

            if($start && $colPos != (count($this->templateHeaders) - 1)) {
                $colPos++;
                continue;
            }

            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->textContent)) {
                    $textLines[$key] = $childNode->textContent;
                }
            }

            if(!empty($textLines)) {
                if($col == 0 && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);

                    # skip incorrect lines (like 39.40 etc.)
                    if(preg_match('/([0-9]+\.[0-9a-zA-Z]+)/', $isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
                    }

                    # append breaked content to previous line
                    if(isset($isBreaked[0]) && preg_match('/([0-9]+\.+)/', $isBreaked[0]) == 0) {

                        if(isset($data[$row-1][$headers[0]])) {
							$data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        }
                        unset($textLines);
                        continue;
                    }
                }

                $text = implode(". ", $textLines);
                if($col == 0) {
                    $data[$row][$headers[$col]] = $text;
                }elseif($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif($col == 4) {
                    $data[$row][$headers[$col-1]] = $text;  // tax
                    $data[$row][$headers[$col]] = '';
                }else {
                    $data[$row][$headers[$col]] = '';
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
    // get quantity and unit column
    private function getQtyUnit($data)
    {
        $qtyUnit = explode(" ", $data);
        if(count($qtyUnit) > 1) {
            $quantity = $qtyUnit[0];
            $unit = $qtyUnit[1];
        }elseif(count($qtyUnit) == 1) {
            if(is_numeric($qtyUnit[0])) {
                $quantity = $qtyUnit[0];
                $unit = '';
            }else {
                $quantity = '';
                $unit = $qtyUnit[0];
            }
        }else {
            $quantity = '';
            $unit = '';
        }
        return [
            'quantity'  => $quantity,
            'unit'      => $unit,
        ];
    }
    private function getTemplate7($lines)
    {
        $data    = [];
        $start   = false;
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $notes   = [];
        $row     = 0;
        $col     = 0;
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            // end of table
            if((in_array($class,['ft014', 'ft04']) && strpos($line->textContent, 'Totals:') !== false)) {
                $start = false;
            }
            if(!$start) {
                if(in_array($class,['ft014', 'ft04']) && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }
                continue;
            }
            // get extra content (notes)
            if (in_array($class, ['ft06','ft016'])) {
                foreach ($line->childNodes as $key => $childNode) {
                    if (isset($childNode->tagName) == 'i') {
                        $notes[] = $childNode->textContent;
                    }
                }
            }
            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }
            if(!empty($textLines)) {
                // append breaked content to previous line (with same class)
                if($col == 0 && $class == 'ft015' && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }
                // append breaked description to previous row
                if($class == 'ft018') {
                    $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                    unset($textLines[0]);
                    if(!count($textLines)) continue;
                }
                $text = implode(". ", $textLines);
                if($class == 'ft06') {
                    $data[$row-1][$headers[0]] .= ' '.$text;
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
    private function getTemplate8($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            if(!$start) {
                if($class == 'ft03' && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }
                continue;
            }
            // end of table
            if($class == 'ft03' && strpos($line->textContent, 'Total:') !== false) {
                break;
            }
            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }
            if(!empty($textLines)) {
                // append breaked description to previous row
                if($class == 'ft05' && isset($textLines[0])) {
                    $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                    unset($textLines[0]);
                    if(!count($textLines)) continue;
                }
                // append breaked content to previous line (with same class)
                if($col == 0 && $class == 'ft04' && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }
                $text = implode(". ", $textLines);
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
    private function getTemplate9($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            // end of page or table
            if($class == 'ft00' || ($class == 'ft02' && strpos($line->textContent, 'Totals:') !== false)) {
                $start = false;
            }
            if(!$start) {
                if($class == 'ft02' && strtolower($line->textContent) == 'rcv') {
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
                // append breaked description to previous row
                if($class == 'ft07' && isset($textLines[0])) {
                    $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                    unset($textLines[0]);
                    $textLines = array_values($textLines);
                    if(!count($textLines)) continue;
                }
                // append breaked content to previous line (with same class)
                if($col == 0 && ($class == 'ft01' || $class == 'ft07') && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
                    }
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }
                $text = implode(". ", $textLines);
                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif($col == 4) {
                    $col++;
                    continue;
                }elseif($col == 5) {
                    $data[$row][$headers[$col-1]] = $text;
                    $data[$row][$headers[$col]]   = 0;
                    $data[$row][$headers[$col+1]] = 0;
                }else {
                    $data[$row][$headers[$col]] = $text;
                }
                $col++;
            }
            if($col == 6){
                $col = 0;
                $row++;
            }
        }
        $this->note = implode("\n", array_filter($notes));
        return $data;
    }
    private function getTemplate10($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $row = 0;
        $col = 0;
        $notes = [];
        $extraCol = false;
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            // end of page or table
            if($class == 'ft00' || (in_array($class, ['ft03', 'ft05', 'ft07']) && strpos($line->textContent, 'Totals:') !== false)) {
                $start = false;
            }
            if(!$start) {
                if(in_array($class, ['ft03', 'ft05', 'ft07']) && strtolower($line->textContent) == 'dep %') {
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
                $text = implode(". ", $textLines);
                if(strpos($text, ' yrs', 0) || ($text == 'Avg.') || strpos($text, '%', 0)) {

                    if(strpos($text, ' yrs', 0)) {
						$extraCol = true;
					}
                    continue;
                }
                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif ($col == 5) {
                    if($extraCol) {
                        $data[$row][$headers[$col]] = $text;
                    }else{
						if($row == 9 && strpos($data[$row]['description'], 'Paint the ceiling - one coat')) {
							$data[$row][$headers[$col]] = $text;
						}else {
							$data[$row][$headers[$col]] = 0;
							$data[$row][$headers[$col+1]] = $text;
							$col++;
						}
					}
                }else {
                    $data[$row][$headers[$col]] = $text;
                }
                $col++;
            }
            if($col == 7){
                $col = 0;
                $extraCol = false;
                $row++;
            }
        }
        $this->note = implode("\n", array_filter($notes));
        return $data;
    }
    private function getTemplate11($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            // end of page or table
            if(in_array($class, ['ft00', 'ft06'])
				|| ($class == 'ft04'
					&& (strpos($line->textContent, 'Totals:') !== false )
						|| (strpos($line->textContent, 'Totals:') !== false ))) {
                $start = false;
                continue;
            }
            if(!$start) {
                if(in_array($class, ['ft04', 'ft03']) && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }
                continue;
            }
            $textLines = [];
            if(in_array($class, ['ft03'])) {
                foreach ($line->childNodes as $key => $childNode) {
                    if(isset($childNode->wholeText)) {
                        $textLines[$key] = $childNode->wholeText;
                    }
                }
            }else {
				foreach ($line->childNodes as $key => $childNode) {
					if(isset($childNode->wholeText)) {
						$textLines[$key] = $childNode->wholeText;
                    }
                }
            }
            if(!empty($textLines)) {
                // append breaked content to previous line (with same class)
                if($col == 0 && (in_array($class, ['ft05', 'ft04'])) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
                    }
                }
                $text = implode(". ", $textLines);
                if(in_array($col, [5, 6, 7])) {
                    $col++;
                    continue;
                }
                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif(in_array($col, [8,9])) {
                    $data[$row][$headers[$col-3]] = $text;
                }else {
                    $data[$row][$headers[$col]] = $text;
                }
                $col++;
            }
            if($col == 10){
                $col = 0;
                $row++;
            }
        }
        $this->note = implode("\n", array_filter($notes));
        return $data;
    }
    private function getTemplate12($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            // end of page or table
            if($class == 'ft00' || ($class == 'ft05' && ( strpos($line->textContent, 'Total:') !== false ) || ( strpos($line->textContent, 'Totals:') !== false ))) {
                $start = false;
            }
            if(!$start) {
                if($class == 'ft04' && strtolower($line->textContent) == 'total') {
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
                # append breaked content to previous line
                if($col == 0 && (in_array($class, ['ft05', 'ft07'])) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
                    }
                }
                $text = implode(". ", $textLines);
                if($col == 1) {
                    # manage unit/qty and description of a row exceptional case
                    if($row == 3 && strpos($data[$row]['description'], ', 1-')) {
                        $text = substr($data[$row]['description'], strpos($data[$row]['description'], ', 1-')+5);
                        $data[$row]['description'] = substr($data[$row]['description'], 0, strpos($data[$row]['description'], ', 1-')+5);
                    }
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif(in_array($col, [2,5])) {
                    if($row == 3 && $col == 5) {
                        $data[$row]['description'] .= $text;
                    }
                    $data[$row][$headers[$col]] = 0;
                }elseif($col == 4 && $row != 3) {
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

    private function getTemplate13($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of page or table
            if($class == 'ft00' || ($class == 'ft05' && ( strpos($line->textContent, 'Total:') !== false ) || ( strpos($line->textContent, 'Totals:') !== false ))) {
                if(isset($data[$row])
					&& isset($data[$row]['description'])
					&& (count($data[$row]) < 8)) {
					unset($data[$row]);
				}
                $start = false;
            }

            if(!$start) {
                if($class == 'ft04' && strtolower($line->textContent) == 'total') {
                    $start = true;
                }

                continue;
            }

            $textLines = [];
            if(in_array($class, ['ft04', 'ft09'])) {
                if($row == 2 && $class == 'ft09') {
                    continue;
                }

                foreach ($line->childNodes as $key => $childNode) {

                    if (isset($childNode->tagName) == 'b') {
                        $textLines[$key] = $childNode->textContent;
                    }
                }
            }else {
                foreach ($line->childNodes as $key => $childNode) {
                    if(isset($childNode->wholeText)) {
                        $textLines[$key] = $childNode->wholeText;
                    }
                }
            }

            if(!empty($textLines)) {

                # append breaked content to previous line
                if($col == 0 && (in_array($class, ['ft05', 'ft04', 'ft06', 'ft08', 'ft09'])) && isset($textLines[0])) {
					if($class == 'ft08') {
						$data[$row-1][$headers[0]] .= ' '.implode(" ", $textLines);
						continue;
					}

                    $isBreaked = explode(' ', $textLines[0]);
					if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {

                        if(!preg_match('/([0-9]+[a-z]\.+)/', $isBreaked[0])) {

                            if(in_array($isBreaked[0], ['TAX', 'O&P', 'RESET'])) {
                                continue;
                            }
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
                    if($row == 5 && $col == 2 && $text == '2.14') {
                        continue;
                    }
                    $data[$row][$headers[$col]] = 0;
                }elseif($col == 4) {
                    $data[$row][$headers[$col-1]] = $text;
                }else {

                    if($row == 59 && $col == 6 && in_array($text, ['1.28', '7.65'])) {
						continue;
					}

                    if($row == 59 && $col == 6 && in_array($text, ['Detach & reset'])) {
						$data[$row]['description'] .= " {$text}";
					}
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }

            if($col == 7){
                $data[$row]['rcv'] = 0;
                $data[$row]['acv'] = 0;
                $col = 0;
                $row++;
            }
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getTemplate14($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        $desStarted = false;
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of table
			if($class == 'ft00'
            || in_array($class, ['ft03', 'ft02']) && strpos($line->textContent, 'Totals:') !== false) {
                $start = false;
                $desStarted = false;

                continue;
            }
            if(!$start) {
                // set $desStarted as true when so that it start to check acv column occurrence
				if(!$desStarted && in_array($class, ['ft03', 'ft02']) && strtolower($line->textContent) == 'description') {
					$desStarted = true;
				}

				if($desStarted && in_array($class, ['ft03', 'ft02']) && strtolower($line->textContent) == 'acv') {
					$start = true;
				}
                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    if(in_array($childNode->wholeText, ['DWELLING', 'OTHER STRUCTURES', 'hour', '001/002 HORSE BARN'])
						|| strpos($childNode->wholeText, 'BLDG') !== false)
						continue;
                    $textLines[$key] = $childNode->wholeText;
                }
            }

            if(!empty($textLines)) {

                if($col == 2) {
					if($textLines[0] == 'PENDING INV.') {
						$data[$row]['price'] = 0.0;
						$data[$row]['tax'] = 0.0;
						$data[$row]['rcv'] = 0.0;
						$data[$row]['depreciation'] = 0;
						$data[$row]['acv'] = 0.0;

						$col = 0;
						$row++;
					}
				}

                # append breaked description to previous row
                if(in_array($class, ['ft015', 'ft05', 'ft06', 'ft07']) && isset($textLines[0])) {
					$textLines = array_values($textLines);
					$lastKey = count($textLines) - 1;

					if($lastKey > 0) {
						$nextLineDescription = $textLines[$lastKey];
						unset($textLines[$lastKey]);
						$data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

						$isBreaked = explode(' ', $nextLineDescription);
						if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
							$data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
							continue;
						}
						$textLines = [
							$lastKey => $nextLineDescription,
						];

					} else {
						$isBreaked = explode(' ', $textLines[0]);

						// split description of previous row and next row if that are merged into a single line
						if($col == 0 && !preg_match('/([0-9]+\.  +)/', $isBreaked[0].' ')
							&& preg_match('!\d+. !', $textLines[0], $matches)) {
							$prevLineDes = substr($textLines[0], 0, strpos($textLines[0], $matches[0]));
							$nextLineDes = substr($textLines[0], strpos($textLines[0], $matches[0]));

							$data[$row-1][$headers[0]] .= ' '.$prevLineDes;
							$textLines = [
								0 => $nextLineDes,
							];
						}
					}
				}

                // append breaked content to previous line (with same class)
                if($col == 0 && in_array($class, ['ft04', 'ft03']) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }

                $text = implode(". ", $textLines);

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

    private function getTemplate15($lines)
    {
        $start = false;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }
            if(!$start) {
                if(in_array($class, ['ft02']) && strtolower($line->textContent) == 'total') {
                    $start = true;
                }

                continue;
            }

            # end of table
            if(($class == 'ft00')
                || ($class == 'ft02' && in_array(strtolower($line->textContent), ['gutters', 'windows', 'Labor Minimums Applied'])))
            {
                $start = false;
                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }

            if(!empty($textLines)) {
                # append breaked content to previous line (with same class)
                if($col == 0 && $class == 'ft03' && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }

                $text = implode(". ", $textLines);

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }else {
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }


            if($col == 4){
                $col = 0;
                $data[$row]['tax'] = 0;
                $data[$row]['rcv'] = 0;
                $data[$row]['depreciation'] = 0;
                $data[$row]['acv'] = 0;
                $row++;
            }
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getTemplate16($lines)
    {
        $start = false;
        $skipInvalidData = true;

        $data = [];

        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of page or table
            if($class == 'ft00' || (in_array($class, ['ft02', 'ft04']) && ( strpos($line->textContent, 'Total:') !== false ) || ( strpos($line->textContent, 'Totals:') !== false ))) {
                $start = false;
            }

            if(!$start) {
                if(($row !== 13) && in_array($class, ['ft02', 'ft04']) && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }elseif($row === 13 && in_array($class, ['ft02']) && strtolower($line->textContent) == 'dep %') {
                    $start = true;
                }

                continue;
            }

            if($skipInvalidData && $row == 0 && $col == 2) {
                $skipInvalidData = false;
                $data = [];
                $col = 0;
                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    $textLines[$key] = $childNode->wholeText;
                }
            }

            if(!empty($textLines)) {
                $text = implode(". ", $textLines);

                if(in_array($col, [5, 6, 7])) {
                    if(in_array($row, [4, 7, 10]) && $col == 6) {
                        $col++;
                    }
                    $col++;

                    continue;
                }

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif(in_array($col, [8,9])) {
                    $data[$row][$headers[$col-3]] = $text;
                }else {
                    $data[$row][$headers[$col]] = $text;
                }

                $col++;
            }

            if($col == 10){
                $col = 0;
                $row++;
            }
        }

        $this->note = implode("\n", array_filter($notes));

        return $data;
    }

    private function getTemplate17($lines)
    {
        $data    = [];
        $start   = false;
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
        $notes   = [];
        $row     = 0;
        $col     = 0;

        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of table
            if($class == 'ft00' || (in_array($class,['ft010', 'ft04']) && strpos($line->textContent, 'Totals:') !== false)) {

                $start = false;
            }

            if(!$start) {
                if(($row !== 12) && in_array($class,['ft010', 'ft04']) && strtolower($line->textContent) == 'acv') {
                    $start = true;
                }elseif ($row === 12 && in_array($class,['ft04']) && strtolower($line->textContent) == 'tax') {
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
                # append breaked content to previous line (with same class)
                if($col == 0 && in_array($class, ['ft011', 'ft05']) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }

                if(in_array($class, ['ft014', 'ft010']) && isset($textLines[0])) {
                    $textLines = array_values($textLines);
                    $lastKey = count($textLines) - 1;

                    if($lastKey > 0) {
                        $nextLineDescription = $textLines[$lastKey];
                        unset($textLines[$lastKey]);
                        $data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

                        $isBreaked = explode(' ', $nextLineDescription);
                        if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                            $data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
                            continue;
                        }
                        $textLines = [
                            $lastKey => $nextLineDescription,
                        ];

                    } else {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines[0]);
                    }

                    if(!count($textLines)) continue;
                }

                $text = implode(". ", $textLines);

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

    private function getTemplate18($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of page or table
            if($class == 'ft00' || ($class == 'ft05' && ( strpos($line->textContent, 'Total:') !== false ) || ( strpos($line->textContent, 'Totals:') !== false ))) {
                $start = false;
            }

            if(!$start) {
                if(($row !== 20) && ($class == 'ft04' && strtolower($line->textContent) == 'total')) {
                    $start = true;
                }elseif(($row === 20) && ($class == 'ft04' && strtolower($line->textContent) == 'reset')) {
                    $start = true;
                }

                continue;
            }

            $textLines = [];
            if($class == 'ft04') {
                foreach ($line->childNodes as $key => $childNode) {

                    if (isset($childNode->tagName) == 'b') {
                        $textLines[$key] = $childNode->textContent;
                    }
                }
            }else {
                foreach ($line->childNodes as $key => $childNode) {
                    if(isset($childNode->wholeText)) {
                        $textLines[$key] = $childNode->wholeText;
                    }
                }
            }

            if(!empty($textLines)) {

                if(in_array($class, ['ft06']) && isset($textLines[0])) {
                    $textLines = array_values($textLines);
                    $lastKey = count($textLines) - 1;

                    if($lastKey > 0) {
                        $nextLineDescription = $textLines[$lastKey];
                        unset($textLines[$lastKey]);
                        $data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

                        $isBreaked = explode(' ', $nextLineDescription);
                        if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                            $data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
                            continue;
                        }
                        $textLines = [
                            $lastKey => $nextLineDescription,
                        ];

                    } else {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        unset($textLines[0]);
                    }

                    if(!count($textLines)) continue;
                }

                # append breaked content to previous line
                if($col == 0 && (in_array($class, ['ft05', 'ft04'])) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
                    }
                }

                $text = implode(". ", $textLines);

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif(in_array($col, [2,5])) {
                    if($row == 8 && $col == 2 && $text == "1.63") {
                        continue;
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

    private function getTemplate19($lines)
    {
        $start = false;
        $data = [];
        $headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

        $row = 0;
        $col = 0;
        $notes = [];
        foreach ($lines as $key => $line) {
            $class = null;
            if($line->hasAttribute('class')) {
                $class = $line->getAttribute('class');
            }

            # end of page or table
            if($class == 'ft00') {
                $start = false;
            }

            if(!$start) {
                if(in_array($class, ['ft04', 'ft03'])&& strtolower($line->textContent) == 'value') {
                    $start = true;
                }

                continue;
            }

            $textLines = [];
            foreach ($line->childNodes as $key => $childNode) {
                if(isset($childNode->wholeText)) {
                    if(in_array($childNode->wholeText, ['DWELLING', 'OTHER STRUCTURES', 'CONTENTS'])) continue;
                    $textLines[$key] = $childNode->wholeText;
                }
            }


            if(!empty($textLines)) {
                # append breaked content to previous line
                if($col == 0 && in_array($class, ['ft07', 'ft04']) && isset($textLines[0])) {
                    $isBreaked = explode(' ', $textLines[0]);
                    if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
                        $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                        unset($textLines);
                        continue;
                    }
                }

                if($col == 0 && in_array($class, ['ft010', 'ft09']) && isset($textLines[0])) {
                    $data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
                    unset($textLines);
                    continue;
                }

                $text = implode(". ", $textLines);

                if($col == 1) {
                    $qtyUnit = $this->getQtyUnit($text);
                    $data[$row]['quantity'] = $qtyUnit['quantity'];
                    $data[$row]['unit'] = $qtyUnit['unit'];
                }elseif(in_array($col, [3])) {
                    $col++;
                    continue;
                }elseif(in_array($col, [4,5,6,7])) {
                    $data[$row][$headers[$col - 1]] = $text;
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

    private function getTemplate20($lines)
	{
		$start = false;
		$data = [];
		$headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
		$row = 0;
		$col = 0;
		$notes = [];
		foreach ($lines as $key => $line) {
			$class = null;
			if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}
			// end of page or table
			if(($class == 'ft00') || (in_array($class, ['ft03', 'ft05']) && (strpos($line->textContent, 'Totals:') !== false)) || (strpos($line->textContent, 'Totals:') !== false)) {
				$start = false;
				continue;
			}
			if(!$start) {
				if(in_array($class, ['ft03', 'ft05', 'ft06']) && strtolower($line->textContent) == 'acv') {
					$start = true;
				}
				continue;
			}
			if((in_array($class, ['ft03', 'ft05', 'ft012',  'ft07', 'ft04']) && $col == 0)) continue;
			$textLines = [];
			foreach ($line->childNodes as $key => $childNode) {
				$textLines[$key] = $childNode->textContent;
			}
			// set all values as 0 if a line has only description
			if($row == 5 && $col == 1 && $class == 'ft02') {
				$data[$row]['quantity'] = 0;
				$data[$row]['quantity'] = 0;
				$data[$row]['unit'] = 0;
				$data[$row]['tax'] = 0;
				$data[$row]['price'] = 0;
				$data[$row]['rcv'] = 0;
				$data[$row]['depreciation'] = 0;
				$data[$row]['acv'] = 0;
				$col = 0;
				$row++;
				continue;
			}
			if(!empty($textLines)) {
				// append breaked content to previous line
				if($col == 0 && in_array($class, ['ft04', 'ft02']) && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
						$data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
						unset($textLines);
						continue;
					}
				}
				$text = implode(". ", $textLines);
				if($col == 1) {
					$col++;
					continue;
				}
				if($col == 2) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				}elseif($col == 4) {
					$data[$row][$headers[$col]] = $text;
				}elseif($col == 3) {
					$data[$row][$headers[$col]] = 0;
					$data[$row][$headers[$col - 1]] = $text;
				}elseif($col > 2) {
					$data[$row][$headers[$col]] = $text;
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

    private function getTemplate21($lines)
	{
		$start = false;
		$data = [];
		$headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
		$row = 0;
		$col = 0;
		$notes = [];
		foreach ($lines as $key => $line) {
			$class = null;
            if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}
			// end of table
			if(($class == 'ft00') || ($class == 'ft04' && strpos($line->textContent, 'Totals:') !== false)) {
				$start = false;
				continue;
			}
            if(!$start) {
				if($class == 'ft04' && strtolower($line->textContent) == 'acv') {
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
				if($class == 'ft07' && isset($textLines[0])) {
					$data[$row-1][$headers[0]] .= ' '.$textLines[0];
					unset($textLines[0]);
					if(!count($textLines)) continue;
				}
				if($col == 0 && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
						$data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
						unset($textLines);
						continue;
					}
				}
				$text = implode(". ", $textLines);
				if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				}elseif($col == 3) {
						$data[$row][$headers[$col]] = '';
						$col++;
						$data[$row][$headers[$col]] = $text;
				}elseif ($col == 7) {
					$key = $col - 2;
					$data[$row][$headers[$key]] = $text;
				}elseif ($col == 8) {
					$key = $col - 2;
					$data[$row][$headers[$key]] = $text;
				}else {
					$data[$row][$headers[$col]] = $text;
				}
				$col++;
			}
			if($col == 9){
				$col = 0;
				$row++;
			}
		}
		$this->note = implode("\n", array_filter($notes));
		return $data;
    }

    private function getTemplate22($lines)
	{
		$start = false;
		$data = [];
		$headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
		$row = 0;
		$col = 0;
		$notes = [];

        foreach ($lines as $key => $line) {
			$class = null;

            if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}

            // end of page or table
			if($class == 'ft00' || (in_array($class, ['ft03', 'ft08', 'ft04']) && ( strpos($line->textContent, 'Total:') !== false ) || ( strpos($line->textContent, 'Totals:') !== false ))) {
				$start = false;
			}

            if(!$start) {
				if(in_array($class, ['ft08', 'ft03', 'ft04']) && strtolower($line->textContent) == 'acv') {
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
				// append breaked content to previous line (with same class)
				if($col == 0 && ($class == 'ft05') && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);

                    if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];
                        continue;
					}
				}
				$text = implode(". ", $textLines);

                if(in_array($col, [5, 6, 7])) {
					$col++;
					continue;
				}

                if($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				}elseif(in_array($col, [8,9])) {
					$data[$row][$headers[$col-3]] = $text;
				}else {
					$data[$row][$headers[$col]] = $text;
				}

                $col++;
			}

            if($col == 10){
				$col = 0;
				$row++;
			}
		}
		$this->note = implode("\n", array_filter($notes));

        return $data;
    }

    public function getTemplate23($lines)
	{
		$start = false;

		$data = [];

		$headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];

		$row = 0;
		$col = 0;
		$notes = [];
		$colPos = 0;
		foreach ($lines as $key => $line) {
			$class = null;
			if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}

			// check end of page/table & set (start = false)
			if($class == 'ft00'	|| (in_array($class, ['ft06', 'ft05', 'ft03', 'ft04', 'ft07']) && (strpos($line->textContent, 'Totals:') !== false || strpos($line->textContent, 'Total:') !== false))) {
				$start = false;
				$colPos = 0;
			}

			if(!$start) {
				if(in_array($class, ['ft03', 'ft06', 'ft05']) && strtolower($line->textContent) == 'description') {
					$start = true;
					$colPos++;
				}

				continue;
			}

			if($start && $colPos != (count($this->templateHeaders) - 1)) {
				$colPos++;
				continue;
			}

			foreach ($line->childNodes as $key => $childNode) {
				if(isset($childNode->textContent)) {
					$textLines[$key] = $childNode->textContent;
				}
			}

			if(!empty($textLines)) {
				if($col == 0 && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);

					# skip incorrect lines (like 39.40 etc.)
					if(preg_match('/([0-9]+\.[0-9a-zA-Z]+)/', $isBreaked[0])) {
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];
						continue;
					}

					# append breaked content to previous line
					if(isset($isBreaked[0]) && preg_match('/([0-9]+\.+)/', $isBreaked[0]) == 0) {
						if(isset($data[$row-1][$headers[0]])) {
							$data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
						}
						unset($textLines);
						continue;
					}
				}

				$text = implode(". ", $textLines);
				if($col == 0) {
					$data[$row][$headers[$col]] = $text;
				}elseif($col == 1) {
					$qtyUnit = $this->getQtyUnit($text);
					$data[$row]['quantity'] = $qtyUnit['quantity'];
					$data[$row]['unit'] = $qtyUnit['unit'];
				}elseif($col == 4) {
					$data[$row][$headers[$col-1]] = $text;	// tax
					$data[$row][$headers[$col]] = 0;
				}else {
					$data[$row][$headers[$col]] = 0;
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

    private function getTemplate24($lines)
	{
		$data 	 = [];
		$start	 = false;
		$headers = ['description', 'quantity_unit', 'price', 'tax', 'rcv', 'depreciation', 'acv'];
		$notes 	 = [];
		$row 	 = 0;
		$col 	 = 0;

		foreach ($lines as $key => $line) {
			$class = null;
			if($line->hasAttribute('class')) {
				$class = $line->getAttribute('class');
			}

			# end of table
			if($class == 'ft00' || (in_array($class,['ft010', 'ft04']) && strpos($line->textContent, 'Totals:') !== false)) {

				$start = false;
			}

			if(!$start) {
				if(in_array($class,['ft08', 'ft04']) && strtolower($line->textContent) == 'acv') {
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
				# append breaked content to previous line (with same class)
				if($col == 0 && in_array($class, ['ft09', 'ft05', 'ft06']) && isset($textLines[0])) {
					$isBreaked = explode(' ', $textLines[0]);
					if(isset($isBreaked[0]) && !is_numeric($isBreaked[0])) {
						$data[$row-1][$headers[0]] .= ' '.implode(' ', $textLines);
						unset($textLines);
						continue;
					}
				}

				if(in_array($class, ['ft012', 'ft08']) && isset($textLines[0])) {
					$textLines = array_values($textLines);
					$lastKey = count($textLines) - 1;

					if($lastKey > 0) {
						$nextLineDescription = $textLines[$lastKey];
						unset($textLines[$lastKey]);
						$data[$row-1][$headers[0]] .= ' '.implode("\n", $textLines);

						$isBreaked = explode(' ', $nextLineDescription);
						if(!preg_match('/([0-9]+\.+)/', $isBreaked[0])) {
							$data[$row-1][$headers[0]] .= "\n{$nextLineDescription}";
							continue;
						}
						$textLines = [
							$lastKey => $nextLineDescription,
						];

					} else {
						$data[$row-1][$headers[0]] .= ' '.$textLines[0];
						unset($textLines[0]);
					}

					if(!count($textLines)) continue;
				}

				$text = implode(". ", $textLines);

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
}