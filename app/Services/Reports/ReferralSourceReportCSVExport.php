<?php
namespace App\Services\Reports;

use Excel;

class ReferralSourceReportCSVExport
{
   public function getCSVReport($data, $type = false)
	{
		Excel::create('report', function($excel) use($data, $type){
			$excel->sheet('sheet1', function($sheet) use($data, $type){
				if($type) {
					$this->setProjectReportData($data, $sheet);
				} else {
					$this->setMarketReportCsvData($data, $sheet);

				}
			});
		})->export('csv');
	}

	public function setMarketReportCsvData($data, $sheet)
	{
		$sheet->fromArray($this->setMarketReportData($data['data']), null, 'A1', true, false);

		// set customer data
		if(ine($data, 'customers')) {
			$customers[] = $this->setCSVDataWithKey($data, 'customers');
			$sheet->fromArray($this->setMarketReportData($customers), null, 'A1', true, false);
		}
		// set website data
		if(ine($data, 'website')) {
			$website[] = $this->setCSVDataWithKey($data, 'website');
			$sheet->fromArray($this->setMarketReportData($website), null, 'A1', true, false);
		}
		//set other data
		if(ine($data, 'others')) {
			$others[] = $this->setCSVDataWithKey($data, 'others');
			$sheet->fromArray($this->setMarketReportData($others), null, 'A1', true, false);
		}
		$sheet->prependRow(1, $this->setMarketReportHeading());

		return $sheet;
	}

	public function setProjectReportData($data, $sheet)
	{
		// set customer data
		if(ine($data, 'customers')) {
			$customers[] = $this->setCSVDataWithKey($data, 'customers');
			$sheet->fromArray($this->setProjectCSVData($customers), null, 'A1', true, false);
		}
		// set website data
		if(ine($data, 'website')) {
			$website[] = $this->setCSVDataWithKey($data, 'website');
			$sheet->fromArray($this->setProjectCSVData($website), null, 'A1', true, false);
		}
		//set other data
		if(ine($data, 'others')) {
			$others[] = $this->setCSVDataWithKey($data, 'others');
			$sheet->fromArray($this->setProjectCSVData($others), null, 'A1', true, false);
		}

		$sheet->fromArray($this->setProjectCSVData($data['data']), null, 'A1', true, false);
		$sheet->prependRow(1, $this->setProjectReportCSVHeading());

		return $sheet;
	}

	/**
	 * set CSV Heading
	 *
	 * @return array
	 */
	public function setMarketReportHeading()
	{
		return [
			'Market Source',
			'Jobs Won (#)',
			'Bad Lead (#)',
			'Total Jobs',
			'Total Amount ($)',
			'Avg Job Price ($)',
			'Avg Profit ($)',
			'Cost/Lead ($)',
			'Cost/Win ($)'
		];
	}

	/**
	 * setMarketReportData
	 * @param [type] $params [description]
	 */
	public function setMarketReportData($params)
	{
		$res = [];
		foreach ($params as $param) {
			$res[] =  [
				'Market Source'  => $param['name'],
				'Jobs Won'       => $param['leads_closed'],
				'Bad Lead'       => $param['bad_leads'],
				'Total Jobs'     => $param['total_leads'],
				'Total Amount'   => showAmount($param['total_jobs_amount']),
				'Avg Job Price ' => showAmount($param['avg_job_price']),
				'Avg Profit'     => showAmount($param['avg_profit']),
				'Cost/Lead'      => showAmount($param['cost_per_lead']),
				'Cost/Win'       => showAmount($param['cost_per_win']),
			];
		}

		return $res;
	}

	private function setProjectReportCSVHeading()
	{
		return [
			'Source',
			'Lead (#)',
			'Bid Amount: # of Jobs',
			'Bid Amount: Total Job Amount($)',
			'Contract Amount: # of Job',
			'Contract Amount: Total Job Amount($)',
			'% of Total',
			'Close Ratio(%)',
		];
	}

	/**
	 * set CSV data
	 * @param  $params
	 */
	public function setProjectCSVData($params)
	{
		$res = [];
		foreach ($params as $param) {
			$res[] =  [
				'Source' => $param['name'],
				'Lead' => $param['total_leads'],
				'total_bid_proposal_jobs' => $param['total_bid_proposal_jobs'],
				'total_bid_proposal_job_amount ' => showAmount($param['total_bid_proposal_job_amount']),
				'total_awarded_jobs' => $param['total_awarded_jobs'],
				'total_awarded_jobs_amount ' => showAmount($param['total_awarded_jobs_amount']),
				'% of Total' => $param['total_rate'],
				'Close Ratio' => $param['closing_rate'],
			];
		}

		return $res;
	}

	/**
	 * set other keys csv data like (customers, website, others)
	 * @param Array 	| $data | Array of data
	 * @param String 	| $key  | key name for which the data is set
	 */
	private function setCSVDataWithKey($data, $key)
	{
		$data[$key]['name'] = $key;

		return $data[$key];
	}
}