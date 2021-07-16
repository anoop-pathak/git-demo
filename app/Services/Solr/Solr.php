<?php

namespace App\Services\Solr;

use App\Models\Customer;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Log;
use Solarium\Client;
use DataMasking;
use Illuminate\Support\Facades\Auth;
use Settings;
use Exception;

class Solr
{
    private $client;

    private $update;

    public function __construct()
    {
        $this->client = new Client(config('solr'));
    }

    /**
     * Job Index
     * @param  int $jobId Job id
     * @return boolean
     */
    public function jobIndex($jobId)
    {
        if (!config('system.enable_solr')) {
            return true;
        }

        $job = Job::whereId($jobId)->first();
        if(!$job) return false;

        setScopeId($job->company_id);

        $job = Job::whereId($jobId)
            ->with(
                'address',
                'customer',
                'customer.phones',
                'customer.contacts',
                'customer.address',
                'jobWorkflow',
                'jobMeta'
            )->first();

        if (!$job) {
            return false;
        }

        if ($job->isProject()) {
            return true;
        }

        try {
            $jobCount = Job::whereCustomerId($job->customer_id)->count();

            //delete customer if  job exist
            if ($jobCount == 1) {
                $update = $this->client->createUpdate();
                $update->addDeleteQuery('doc_id:' . $job->customer_id);
                $update->addCommit();
                $this->client->update($update);
            }
            self::jobDataIndex($job);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    /**
     * Job Delete
     * @param  int $id job id
     * @return boolean
     */
    public function jobDelete($jobId, $customerId)
    {
        if (!config('system.enable_solr')) {
            return true;
        }
        try {
            $update = $this->client->createUpdate();
            $update->addDeleteQuery('job_id:' . $jobId . ' OR ' . 'parent_id:' . $jobId);
            $update->addCommit();
            $this->client->update($update);
            $customer = Customer::find($customerId);
            if(!$customer) return false;

            setScopeId($customer->company_id);
            if($customer && !($customer->jobs()->count())) {
                self::customerDataIndex($customer);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    /**
     * Customer Index
     * @param  int $customerId customer id
     *  @param  Object | $customer | Customer model object
     * @return boolean
     */
    public function customerIndex($customerId, $customer = null)
    {

        if (!config('system.enable_solr')) {
            return true;
        }

        if(!$customer) {
            $customer = Customer::whereId($customerId)
                ->with('phones', 'contacts', 'address')
                ->first();
        }

        if (!$customer) {
            return false;
        }

        setScopeId($customer->company_id);

        try {
            if ($customer->allJobs()->count()) {
                $this->customerJobsIndex($customer);
            } else {
                $this->customerDelete($customer->id);
                $this->customerDataIndex($customer);
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    /**
     * Customer Search
     * @param  string $keyword keyword
     * @param  integer $page page id
     * @param  integer $limit limit
     * @param  companyId int
     * @return response
     */
    public function customerSearch($keyword)
    {
        try {
            $limit = config('jp.pagination_limit');

            if (\Request::has('limit')) {
                $limit = (int)Request::get('limit');
            }

            $page = Request::get('page') ?: 1;

            $query = $this->client->createSelect();

            //get only company jobs
            $query->addFilterQuery([
                'key' => 'company_id',
                'query' => 'company_id:' . getScopeId(),
                'tag' => 'include'
            ]);

            // exclude projects
            $query->addFilterQuery([
                'key' => 'parent_id',
                'query' => 'parent_id:0',
                'tag' => 'include'
            ]);

            //get only jobs

            $query->setQuery(strtolower($keyword));
            $query->setStart(($page - 1) * $limit)->setRows($limit);
            $dismax = $query->getDisMax();

            $query->addSorts([
                'score' => $query::SORT_DESC,
                '_version_' => $query::SORT_DESC
            ]);

           $dismax->setPhraseFields('full_name^800 first_name^500 last_name^250 secondary_name^200  full_name_reverse^300 customer_contact_first_name^100 customer_contact_last_name^110 company_name^230 customer_address^220 customer_address_line_1^120 customer_city^210 customer_state^210  customer_state_code^210 phone_number^130 customer_zip^20 email^10');

           if(DataMasking::isEnable()) {
                $dismax->setQueryFields('full_name secondary_name first_name last_name full_name_reverse customer_contact_first_name customer_contact_last_name company_name customer_address customer_address_line_1 customer_city customer_state  customer_state_code customer_zip');
            }else {
                $dismax->setQueryFields('full_name secondary_name first_name last_name full_name_reverse customer_contact_first_name customer_contact_last_name company_name customer_address customer_address_line_1 customer_city customer_state  customer_state_code phone_number customer_zip email');
            }

            $resultset = $this->client->select($query);
            $result = $resultset->getData();

            return array_unique(array_column($result['response']['docs'], 'customer_id'));
        } catch (\Exception $e) {
            $result = null;
            $message = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Solr Customer Search: ' . $message);

            return [];
        }
    }

    /**
     * Customer search by name
     * @param  [type] $keyword   [description]
     * @param  [type] $companyId [description]
     * @return [type]            [description]
     */
    public function customerSearchByName($keyword)
    {
        try {
            $query = $this->client->createSelect();

            //get only company jobs
            $query->addFilterQuery([
                'key' => 'company_id',
                'query' => 'company_id:' . getScopeId(),
                'tag' => 'include'
            ]);

            // exclude projects
            $query->addFilterQuery([
                'key' => 'parent_id',
                'query' => 'parent_id:0',
                'tag' => 'include'
            ]);

            $query->setQuery(strtolower($keyword));
            $query->setStart(0)->setRows(100);
            $dismax = $query->getDisMax();

            $query->addSorts([
                'score' => $query::SORT_DESC,
                '_version_' => $query::SORT_DESC
            ]);

            $dismax->setPhraseFields('full_name^800 first_name^500 last_name^250 full_name_reverse^300 customer_contact_first_name^240 customer_contact_last_name^240');

            $dismax->setQueryFields('full_name customer_contact_first_name customer_contact_last_name first_name last_name full_name_reverse');

            $resultset = $this->client->select($query);
            $result = $resultset->getData();

            return array_unique(array_column($result['response']['docs'], 'customer_id'));
        } catch (\Exception $e) {
            $result = null;
            $message = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Solr Customer Search By Name: ' . $message);

            return [];
        }
    }

    /**
     * Job Search
     * @param  string $keyword keyword
     * @param  integer $page page id
     * @param  integer $limit limit
     * @param  companyId int
     * @return response
     */
    public function jobSearch($keyword, $page = 0, $limit = 10, $companyId, $filter = [])
    {
        $settings = Settings::get('JOB_SEARCH_SCOPE');
		try {
			$query = $this->client->createSelect();

			//get only company jobs
			$query->addFilterQuery([
				'key'   => 'company_id',
				'query' => 'company_id:'.$companyId,
				'tag'   => 'include'
			]);

			// exclude projects
			$query->addFilterQuery([
				'key'   => 'parent_id',
				'query' => 'parent_id:0',
				'tag'   => 'include'
			]);

		   	//get only jobs
			if(ine($filter, 'with_job')) {
				$query->addFilterQuery([
					'key'   => 'with_job',
					'query' => 'with_job:'.true,
					'tag'   => 'include'
				]);
			}

			//get jobs having lost and archive jobs
			if(!empty($settings)) {
				if((!$settings['include_lost_jobs']) && (!$settings['include_archived_jobs'])) {
					$query->addFilterQuery([
						'key'   => 'jobs',
						'query' => '-archived:[* TO *] & -lost_date:[* TO *]',
						'tag'   => 'include'
					]);
				} elseif ($settings['include_lost_jobs'] && (!$settings['include_archived_jobs'])) {
					$query->addFilterQuery([
						'key'	=>	'lost_date',
						'query'	=>	'-archived:[* TO *]',
						'tag'	=>	'include'
					]);
				} elseif ($settings['include_archived_jobs'] && (!$settings['include_lost_jobs'])) {
					$query->addFilterQuery([
						'key'	=>	'archived',
						'query'	=>	'-lost_date:[* TO *]',
						'tag'	=>	'include'
					]);
				}
			}

			if(!(Auth::user()->isOwner() || Auth::user()->isAnonymous() || Auth::user()->all_divisions_access) ) {
				if(!empty($division = Auth::user()->divisions->pluck('id')->toArray())) {
					array_push($division, 0);
					$query->addFilterQuery([
						'key'   => 'division_id',
						'query' => 'division_id:'.implode(', ', $division),
						'tag'   => 'include'
					]);
				} else {
					$query->addFilterQuery([
						'key'   => 'division_id',
						'query' => 'division_id:0',
						'tag'   => 'clude'
					]);
				}
			}

			$query->setQuery(strtolower($keyword));
			$query->setStart(($page - 1) * $limit)->setRows($limit);
			$dismax = $query->getDisMax();

			$query->addSorts([
				'score'     => $query::SORT_DESC,
				'_version_' => $query::SORT_DESC
			]);

			 $dismax->setPhraseFields('job_name^800 full_name^800 first_name^500 last_name^250 full_name_reverse^300 customer_contact_first_name^240 customer_contact_last_name^240 company_name^230 customer_address^220 customer_address_line_1^220 customer_city^210 customer_state^210  customer_state_code^210 job_address^180 job_address_line_1^170 job_state^160 job_city^150 phone_number^130 number^110 alt_id^210 full_alt_id^210 customer_zip^20 job_zip^20');

			$resultset = $this->client->select($query);
			$result = $resultset->getData();

		} catch(\Exception $e) {
			$result = null;
			$message = $e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine();
			Log::error('Solr Job Search: '. $message);
		}

		$paginationMeta['pagination'] = [
				'total'        => (int)$result['response']['numFound'],
				'count'        => (int)$result['response']['numFound'],
				'per_page'     => $limit,
				'current_page' => $page,
				'total_pages'  => ceil($result['response']['numFound']/$limit),
		];

		$documentMeta = [
			'documents'       => $result['response']['docs'],
			'pagination_meta' => $paginationMeta
		];

		return $documentMeta;
    }

    public function getJobIdsAfterSearch($keyword, $companyId, $filters = array())
    {
        try {
            $query = $this->client->createSelect();
            //get only company jobs
            $query->addFilterQuery([
                'key'   => 'company_id',
                'query' => 'company_id:'.$companyId,
                'tag'   => 'include'
            ]);
            // exclude projects
           $query->addFilterQuery([
                'key'   => 'parent_id',
                'query' => 'parent_id:0',
                'tag'   => 'include'
            ]);
           //get only jobs
           if(ine($filters, 'with_job')) {
                $query->addFilterQuery([
                    'key'   => 'with_job',
                    'query' => 'with_job:'.true,
                    'tag'   => 'include'
                ]);
           }
            if(!(Auth::user()->isOwner() || Auth::user()->isAnonymous() || Auth::user()->all_divisions_access) ) {
                if(!empty($division = Auth::user()->divisions->pluck('id')->toArray())) {
                    array_push($division, 0);
                    $query->addFilterQuery([
                        'key'   => 'division_id',
                        'query' => 'division_id:'.implode(', ', $division),
                        'tag'   => 'include'
                    ]);
                } else {
                    $query->addFilterQuery([
                        'key'   => 'division_id',
                        'query' => 'division_id:0',
                        'tag'   => 'clude'
                    ]);
                }
            }
            $query->setQuery(strtolower($keyword));
            $query->setStart(0)->setRows(100);
            $dismax = $query->getDisMax();
            $query->addSorts([
                'score'     => $query::SORT_DESC,
                '_version_' => $query::SORT_DESC
            ]);
            if(DataMasking::isEnable()) {
                $dismax->setQueryFields(['job_name', 'full_name', 'first_name', 'last_name', 'full_name_reverse', 'customer_contact_first_name', 'customer_contact_last_name', 'company_name', 'customer_address', 'customer_address_line_1', 'customer_city', 'customer_state', ' customer_state_code', 'job_address', 'job_address_line_1', 'job_state', 'job_city', 'number', 'alt_id', 'customer_zip', 'job_zip', 'full_alt_id']);
            }
            $dismax->setPhraseFields('job_name^800 full_name^800 first_name^500 last_name^250 full_name_reverse^300 customer_contact_first_name^240 customer_contact_last_name^240 company_name^230 customer_address^220 customer_address_line_1^220 customer_city^210 customer_state^210  customer_state_code^210 job_address^180 job_address_line_1^170 job_state^160 job_city^150 phone_number^130 number^110 alt_id^210 full_alt_id^210 customer_zip^20 job_zip^20');
            $resultset = $this->client->select($query);
            $result = $resultset->getData();
            return array_unique(array_column($result['response']['docs'], 'job_id'));
        } catch(\Exception $e) {
            $result = null;
            $message = $e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine();
            Log::error('Solr Job Ids Search: '. $message);

            return [];
        }
    }

    /**
     * Customer Delete
     * @param  int $customerId Customer Id
     * @return boolean
     */
    public function customerDelete($customerId)
    {
        if (!config('system.enable_solr')) {
            return true;
        }
        try {
            $update = $this->client->createUpdate();
            $update->addDeleteQuery('customer_id:' . $customerId);
            $update->addCommit();
            $this->client->update($update);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    /**
	 * Customer Delete
	 * @param  int $customerId  Customer Id
	 * @return boolean
	 */
	public function allCustomersDelete($companyId)
	{
		if(!config('app.enable_solr')) {

			return true;
		}
		try {
			$update = $this->client->createUpdate();
			$update->addDeleteQuery('company_id:'.$companyId);
			$update->addCommit();
			$this->client->update($update);
		}  catch(Exception $e) {
			Log::error($e);
		}

		return true;
	}

    /**
     * Delete customer and their jobs
     * @param  int $customerId id of customer
     * @return boolean
     */
    public function customerJobDelete($customerId)
    {
        if (!config('system.enable_solr')) {
            return true;
        }

        try {
            $update = $this->client->createUpdate();
            $update->addDeleteQuery('customer_id:' . $customerId);
            $update->addCommit();
            $this->client->update($update);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return true;
    }

    /**
     * Check solr is running
     * @return boolean
     */
    public function isRunning()
    {
        if (!config('system.enable_solr')) {
            return false;
        }


        try {
            $ping = $this->client->createPing();
            $result = $this->client->ping($ping);
            $data = $result->getData();

            return (ine($data, 'status') && ($data['status'] === "OK"));
        } catch (\Exception $e) {
            Log::error('Solr: ping failed. Error detail: ' . getErrorDetail($e));

            return false;
        }
    }

    /*************PRIVATE METHOD************/

    private function customerJobsIndex($customer)
    {
        $jobs = $customer->allJobs;
        $phones = $customer->phones;
        $contacts = $customer->contacts;
        //format phone data
        $phoneData = $phoneNumbers = [];
        foreach ($phones as $key => $phone) {
            $phoneData[] = $phone['label'] . '_SEP_' . $phone['number'] . '_SEP_' . $phone['ext'];
            $phoneNumbers[] = $phone['number'];
        }

        $update = $this->client->createUpdate();

        foreach ($jobs as $key => $job) {
            $jobFollowUp = $job->currentFollowUpStatus()->first();
            $doc[$key] = $update->createDocument();
            //get job current stage data
            $currentStage = $job->getCurrentStage();

            $trades = $job->trades->pluck('name')->toArray();

            //customer data
            $doc[$key]->doc_id = $customer->id . '_' . $job->id;
            $doc[$key]->customer_id = $customer->id;
            $doc[$key]->first_name = $customer->first_name;
            $doc[$key]->last_name = $customer->last_name;
            $doc[$key]->email = $customer->email;
            $doc[$key]->company_name = $customer->company_name;
            $doc[$key]->full_name = $customer->first_name . ' ' . $customer->last_name . ' ' . $customer->company_name;
            $doc[$key]->full_name_reverse = $customer->last_name . ' ' . $customer->first_name . ' ' . $customer->company_name;
            $doc[$key]->company_id = $customer->company_id;
            if ($address = $customer->address) {
                $doc[$key]->customer_address = $address->address;
                $doc[$key]->customer_address_line_1 = $address->address_line_1;
                $doc[$key]->customer_city = $address->city;
                $doc[$key]->customer_zip = $address->zip;
                $doc[$key]->customer_state = isset($address->state->name) ? $address->state->name : '';
                $doc[$key]->customer_country = isset($address->country->name) ? $address->country->name : '';
            }
            $doc[$key]->is_commercial = $customer->is_commercial;

            //customer contact name map
            $contactFirstName = null;
            $contacLastName = null;
            foreach ($contacts as $contact) {
                $contactFirstName = $contact->first_name;
                $contacLastName = $contact->last_name;
            }
            $doc[$key]->customer_contact_first_name = $contactFirstName;
            $doc[$key]->customer_contact_last_name = $contacLastName;
            $doc[$key]->secondary_name = $contactFirstName . ' ' . $contacLastName;
            //job data map
            $doc[$key]->trades = $trades;
            $doc[$key]->job_name = $job->name;
            $doc[$key]->number = $job->number;
            $doc[$key]->alt_id = $job->alt_id;
            $doc[$key]->job_id = $job->id;
            if ($address = $job->address) {
                $doc[$key]->job_address = $address->address;
                $doc[$key]->job_address_line_1 = $address->address_line_1;
                $doc[$key]->job_city = $address->city;
                $doc[$key]->job_zip = $address->zip;
                $doc[$key]->job_state = isset($address->state->name) ? $address->state->name : '';
                $doc[$key]->job_country = isset($address->country->name) ? $address->country->name : '';
            }
            $doc[$key]->job_resource_id = $job->getResourceId();
            $doc[$key]->phones = $phoneData;
            $doc[$key]->phone_number = $phoneNumbers;
            $doc[$key]->multi_job = $job->multi_job;
            $doc[$key]->parent_id = ($job->parent_id) ? $job->parent_id : 0;
            $doc[$key]->archived = $job->archived;
			$doc[$key]->lost_date  = null;
			if($jobFollowUp && $jobFollowUp->mark == 'lost_job') {
				$doc[$key]->lost_date = $jobFollowUp->date_time;
			}

            //map current stage
            $doc[$key]->current_stage_name = $currentStage['name'];
            $doc[$key]->current_stage_color = $currentStage['color'];
            $doc[$key]->current_stage_code = $currentStage['code'];
            $doc[$key]->current_stage_resource_id = $currentStage['resource_id'];
            $doc[$key]->other_trade_type_description = $job->other_trade_type_description;
            $doc[$key]->with_job = true;

            if($division = $job->division) {
				$doc[$key]->division    = $division->name;
            }
            $doc[$key]->division_code = $job->division_code;
 			$doc[$key]->division_id = $job->division_id;
        }

        $update->addDocuments($doc);
        $update->addCommit();
        $this->client->update($update);

        DB::table('jobs')->whereCustomerId($customer->id)
            ->update(['solr_sync' => true]);

        DB::table('customers')->whereId($customer->id)
            ->update(['solr_sync' => true]);
    }

    /**
     * Index job
     * @param  Job $job jobObject
     * @return boolean
     */
    private function jobDataIndex(Job $job)
    {
        $jobFollowUp = $job->currentFollowUpStatus()->first();
        $customer = $job->customer;
        $phones = $customer->phones;
        $contacts = $customer->contacts;
        $update = $this->client->createUpdate();
        $doc = $update->createDocument();

        //format phone data
        $phoneData = $phoneNumbers = [];
        foreach ($phones as $key => $phone) {
            $phoneData[] = $phone['label'] . '_SEP_' . $phone['number'] . '_SEP_' . $phone['ext'];
            $phoneNumbers[] = $phone['number'];
        }

        //get job current stage data
        $currentStage = $job->getCurrentStage();

        $trades = $job->trades->pluck('name')->toArray();

        //customer data
        $doc->doc_id                  = $customer->id .'_'.$job->id;
		$doc->customer_id             = $customer->id;
		$doc->first_name              = $customer->first_name;
		$doc->last_name               = $customer->last_name;
		$doc->email                   = $customer->email;
		$doc->company_name            = $customer->company_name;
		$doc->full_name               = $customer->first_name . ' ' . $customer->last_name . ' '.$customer->company_name;
		$doc->full_name_reverse       = $customer->last_name . ' '. $customer->first_name . ' '.$customer->company_name;
		$doc->company_id              = $customer->company_id;
        if ($address = $customer->address) {
            $doc->customer_address = $address->address;
            $doc->customer_address_line_1 = $address->address_line_1;
            $doc->customer_city = $address->city;
            $doc->customer_zip = $address->zip;
            $doc->customer_state = isset($address->state->name) ? $address->state->name : '';
            $doc->customer_country = isset($address->country->name) ? $address->country->name : '';
        }
        $doc->is_commercial = $customer->is_commercial;

        //customer contact name map
        $contactFirstName = null;
        $contacLastName = null;
        foreach ($contacts as $key => $contact) {
            $contactFirstName = $contact->first_name;
            $contacLastName = $contact->last_name;
        }
        $doc->customer_contact_first_name = $contactFirstName;
        $doc->customer_contact_last_name = $contacLastName;
        $doc->secondary_name = $contactFirstName . ' ' . $contacLastName;

        //job data map
        $doc->trades   = $trades;
		$doc->job_name = $job->name;
		$doc->number   = $job->number;
		$doc->archived  = $job->archived;
        $doc->lost_date = null;

		if($jobFollowUp && $jobFollowUp->mark == 'lost_job') {
			$doc->lost_date	  = $jobFollowUp->date_time;
		}

        $doc->alt_id = $job->alt_id;
		$doc->job_id = $job->id;
        if ($address = $job->address) {
            $doc->job_address = $address->address;
            $doc->job_address_line_1 = $address->address_line_1;
            $doc->job_city = $address->city;
            $doc->job_zip = $address->zip;
            $doc->job_state = isset($address->state->name) ? $address->state->name : '';
            $doc->job_country = isset($address->country->name) ? $address->country->name : '';
        }
        $doc->job_resource_id = $job->getResourceId();
        $doc->phones = $phoneData;
        $doc->phone_number = $phoneNumbers;
        $doc->multi_job = $job->multi_job;
        $doc->parent_id = ($job->parent_id) ? $job->parent_id : 0;

        //map current stage
        $doc->current_stage_name = $currentStage['name'];
        $doc->current_stage_color = $currentStage['color'];
        $doc->current_stage_code = $currentStage['code'];
        $doc->current_stage_resource_id = $currentStage['resource_id'];
        $doc->other_trade_type_description = $job->other_trade_type_description;
        $doc->with_job = true;

        if($division = $job->division) {
			$doc->division    = $division->name;
		}
        $doc->division_id = $job->division_id;
        $doc->full_alt_id = $job->full_alt_id;
        $doc->division_code = $job->division_code;

        $update->addDocuments([$doc]);
        $update->addCommit();
        $this->client->update($update);

        DB::table('jobs')->whereId($job->id)
            ->update(['solr_sync' => true]);

        return true;
    }

    /**
     * Customer Data Index
     * @param  object $customer Customer
     * @return Boolean
     */
    private function customerDataIndex($customer)
    {
        $phones = $customer->phones;
        $contacts = $customer->contacts;
        $update = $this->client->createUpdate();
        $doc = $update->createDocument();

        //phone data map
        $phoneData = $phoneNumbers = [];
        foreach ($phones as $key => $phone) {
            $phoneData[] = $phone['label'] . '_SEP_' . $phone['number'] . '_SEP_' . $phone['ext'];
            $phoneNumbers[] = $phone['number'];
        }
        $doc->doc_id = $customer->id;
        $doc->customer_id = $customer->id;
        $doc->first_name = $customer->first_name;
        $doc->last_name = $customer->last_name;
        $doc->email = $customer->email;
        $doc->company_name = $customer->company_name;
        $doc->company_id = $customer->company_id;
        $doc->with_job = false;
        $doc->is_commercial = $customer->is_commercial;
        if ($address = $customer->address) {
            $doc->customer_address = $address->address;
            $doc->customer_address_line_1 = $address->address_line_1;
            $doc->customer_city = $address->city;
            $doc->customer_zip = $address->zip;
            $doc->customer_state = isset($address->state->name) ? $address->state->name : '';
            $doc->customer_country = isset($address->country->name) ? $address->country->name : '';
        }
        $doc->phones = $phoneData;
        $doc->phone_number = $phoneNumbers;
        $doc->parent_id = 0;

        //map customer contact page
        $contactFirstName = null;
        $contacLastName = null;
        foreach ($contacts as $key => $contact) {
            $contactFirstName = $contact->first_name;
            $contacLastName = $contact->last_name;
        }
        $doc->customer_contact_first_name = $contactFirstName;
        $doc->customer_contact_last_name = $contacLastName;
        $update->addDocuments([$doc]);
        $update->addCommit();
        $this->client->update($update);

        DB::table('customers')->whereId($customer->id)
            ->update(['solr_sync' => true]);

        return true;
    }
}
