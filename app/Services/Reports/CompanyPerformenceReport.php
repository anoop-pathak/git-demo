<?php
namespace App\Services\Reports;

use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Excel;

class CompanyPerformenceReport extends AbstractReport
{
    protected $scope;
    protected $jobRepo;

    // protected $weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];

    function __construct(Context $scope, JobRepository $jobRepo)
    {
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
    }

    /**
     * return data for company performace report
     *
     * @param $filters (array)
     * @return $data(array)
     */
    public function get($filters = [])
    {
        //check JOB_AWARDED_STAGE is set
        $jobAwardedStage = $this->getJobAwardedStage();

        //set date filters
        $filters = $this->setDateFilter($filters);

        if(ine($filters, 'duration') && ($filters['duration'] == 'since_inception')) {
			$filters['start_date'] = $this->scope->getSinceInceptionDate();
		}

        // make Carbon object of date string
        $filters['start_date'] = Carbon::parse($filters['start_date']);
        $filters['end_date'] = Carbon::parse($filters['end_date']);

        //get report data
        $data['data'] = $this->getData($filters);

        if(ine($filters, 'duration') && ($filters['duration'] == 'since_inception')) {
            $data['meta']['company']['created_at'] = $this->scope->getSinceInceptionDate();
        }

        if(ine($filters, 'csv_export')) {
			return $this->csvExport($data['data']);
		}

        return $data;
    }

    /**
	 * export csv
	 *
	 * GET - /company_performance/csv_export
	 *
	 * @return response
	 */
	public function csvExport($data)
	{
		$reportData = [];
		foreach ($data as $key => $value) {
			$report['Duration']	= $key;
			$report['Total Jobs']	=(string) $value['total_jobs'];
			$report['Lost Jobs']	= (string) $value['lost_jobs'];
			$report['Jobs Won']	= (string) $value['closed'];

			$reportData[] =  $report;
		}

		if(empty($reportData)) {
			$reportData = $this->getDefaultColumns();
		}

		Excel::create('Company_Performance_Report', function($excel) use($reportData){
			$excel->sheet('sheet1', function($sheet) use($reportData){
				$sheet->fromArray($reportData);
			});
		})->export('csv');
	}

    /***** Private Functions *****/

    /**
     * return formatted data according to date filter
     *
     * @param $filters (array)
     * @return $data(array)
     */
    private function getData($filters)
    {
        $diff = $filters['start_date']->diff($filters['end_date']);

        /* get year wise */
        if ($diff->y >= 1) {
            return $this->getYearWise($filters);
        }

        /* get month wise */
        if ($diff->m >= 1) {
            return $this->getMonthWise($filters);
        }

        /* get day wise */
        if ($diff->m < 1) {
            return $this->getDayWise($filters);
        }

        /* get week wise */
        /*if($diff->m <= 1 && $diff->y < 1) {

			return $this->getWeekWise($filters);
		}*/
    }

    /**
     * return data in years format
     *
     * @param $filters (array)
     * @return $data(array)
     */
    private function getYearWise($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $endDay = $endDate->day;
        $endMonth = $endDate->month;

        $lastYear = $endDate->year - $startDate->year;

        /*return null if date duration in minus*/
        if ($lastYear < 1) {
            return [];
        }

        for ($i = 0; $i <= $lastYear; $i++) {
            /* set last day of month */
            $lastDayOfMonth = $startDate->copy()->endOfMonth()->day;

            /*if last month to get jobs then set end day according to filter end date*/
            if ($i == $lastYear) {
                $lastDayOfMonth = $endDay;
            }

            $endMonthOfYear = $startDate->copy()->endOfYear()->month;

            /*if last year to get jobs then set end month according to filter end date*/
            if ($i == $lastYear) {
                $endMonthOfYear = $endMonth;
            }

            /* set month filter */
            $filters['start_date'] = $startDate->copy()->toDateString();
            $filters['end_date'] = $startDate->copy()->month($endMonthOfYear)->day($lastDayOfMonth)->toDateString();

            /* set report data */
            $fullData = $this->getAllLeads($filters);

            $data[$startDate->year]['total_jobs'] = $fullData['total_jobs'];
			$data[$startDate->year]['closed'] 	  = $fullData['closed_jobs'];
			$data[$startDate->year]['lost_jobs']  = $fullData['lost_jobs'];

            /*add one year in start date*/
            $startDate = $startDate->copy()->addYears(1)->month(1)->day(1);
        }

        return $data;
    }

    /**
     * return data in months format
     *
     * @param $filters (array)
     * @return $data(array)
     */
    private function getMonthWise($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $startMonth = $startDate->month;
        $startDay = $startDate->day;
        $endDay = $endDate->day;

        $lastMonth = $endDate->month - $startMonth;

        /* only if duration like 2 Jan 2015 to 1 Jan 2016 */
        $yearDiff = $endDate->year - $startDate->year;
        $lastMonth = $lastMonth + (12 * $yearDiff);
        /****/

        /*return null if date duration in minus*/
        if ($lastMonth < 0) {
            return [];
        }

        for ($i = 0; $i <= $lastMonth; $i++) {
            /* set last day of month */
            $endDayOfMonth = $startDate->copy()->endOfMonth()->day;

            /*if last month to get jobs then set end day according to filter end date*/
            if ($i == $lastMonth) {
                $endDayOfMonth = $endDay;
            }

            /* set month filter */
            $filters['start_date'] = $startDate->copy()->day($startDay)->toDateString();
            $filters['end_date'] = $startDate->copy()->day($endDayOfMonth)->toDateString();

            /* set report data */
            $fullData = $this->getAllLeads($filters);

			$data[$startDate->formatLocalized('%b-%Y')]['total_jobs'] = $fullData['total_jobs'];
			$data[$startDate->formatLocalized('%b-%Y')]['closed']     = $fullData['closed_jobs'];
			$data[$startDate->formatLocalized('%b-%Y')]['lost_jobs']  = $fullData['lost_jobs'];

            /* set start day of month = 1 */
            if ($i == 0) {
                $startDay = $startDate->copy()->startOfMonth()->day;
            }

            /* addone month in start date */
            $startDate = $startDate->copy()->addMonth(1);
        }

        return $data;
    }

    /**
     * return data in days format
     *
     * @param $filters (array)
     * @return $data(array)
     */
    private function getDayWise($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        /* to apply created_date filter */
        $filters['start_date'] = null;
        $filters['end_date'] = null;

        $endDay = $endDate->day;

        /*return null if date duration in minus (for month)*/
        $lastMonth = $endDate->month - $startDate->month;
        if ($lastMonth < 0) {
            return [];
        }

        /*return null if date duration in minus (for days)*/
        $lastDay = $endDate->day - $startDate->day;
        if ($lastDay < 0) {
            return [];
        }

        for ($i = 0; $i <= $lastDay; $i++) {
            /* set month filter */
            $filters['created_date'] = $startDate->copy()->toDateString();

            /* set report data */
            $fullData = $this->getAllLeads($filters);

			$data[$startDate->formatLocalized('%d-%m-%Y')]['total_jobs'] = $fullData['total_jobs'];
			$data[$startDate->formatLocalized('%d-%m-%Y')]['closed'] 	 = $fullData['closed_jobs'];
			$data[$startDate->formatLocalized('%d-%m-%Y')]['lost_jobs']  = $fullData['lost_jobs'];

            /*add one day in start date*/
            $startDate = $startDate->addDays(1);
        }

        return $data;
    }

    /**
     * return data in weeks format
     *
     * @param $filters (array)
     * @return $data(array)
     */
    private function getWeekWise($filters)
    {
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $endDay = $endDate->copy()->dayOfWeek;

        $lastWeek = $endDate->weekOfYear - $startDate->weekOfYear;

        /*return null if date duration in minus*/
        if ($lastWeek < 1) {
            return [];
        }

        for ($i = 0; $i <= $lastWeek; $i++) {
            if ($i == 0) {
                $addDays = $startDate->copy()->endOfWeek()->day - $startDate->day + 1;
            }

            if ($i == $lastWeek) {
                $addDays = $endDay;
            }
            /* set month filter */
            $filters['start_date'] = $startDate->copy();
            $filters['end_date'] = $startDate->addDays($addDays)->endOfDay();

            /* set report data */
            $fullData = $this->getAllLeads($filters);

			$data[$this->weeks[$i]]['total_jobs'] = $fullData['total_jobs'];
			$data[$this->weeks[$i]]['closed']     = $fullData['closed_jobs'];
			$data[$this->weeks[$i]]['lost_jobs']  = $fullData['lost_jobs'];

            $addDays = 7;
        }

        return $data;
    }

    private function getAllLeads($filters)
	{
		/* get filtered jobs */
		$joins = ['awarded_stage'];
		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);

		$totalJobs = $jobs->excludeBadLeads()->count();
		$closed    = $jobs->closedJobs()->count();

		$filters['follow_up_marks'][] = 'lost_job';
		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins);
		$lostJob =  $jobs->count();

		$data = [
			'total_jobs' => $totalJobs,
			'closed_jobs'=> $closed,
			'lost_jobs'  => $lostJob
		];

		return $data;
	}

	private function getDefaultColumns()
	{
		return [
			'Duration',
			'Total Jobs',
			'Lost Jobs',
			'Jobs Won '
		];
	}
}
