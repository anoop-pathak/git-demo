<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\CompanyMeta;
use App\Models\FinancialCategory;
use App\Models\ProductionBoard;
use App\Models\ProductionBoardColumn;
use App\Models\Resource;
use App\Models\Trade;
use App\Services\Resources\ResourceServices;
use App\Services\Solr\Solr;
use App\Services\Grid\CommanderTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Models\EstimatePitch;
use App\Models\PaymentMethod;
use App\Models\ProposalViewer;
use App\Models\AppointmentResultOption;
use App\Models\TierLibrary;
use App\Models\Referral;

class CompanySetup
{

    /**
     * App\Resources\ResourceServices;
     */
    protected $resourceService;

    use CommanderTrait;

    public function __construct(ResourceServices $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
     * Company base setup
     * @param Company Object
     */
    public function run(Company $company)
    {
        try {
            $this->createBaseDirectories($company);
            $this->createFinacialCategoroies($company->id);
            $this->createProductionBoardColumns($company->id);
            $this->createClickthruPitch($company->id);
            $this->createDefaultPaymentMethod($company->id);
            $this->saveDefaultProposalViewer($company);
            $this->saveDefaultTiers($company);
            $this->saveDefaultAppointmentResultOptions($company->id);
            $this->saveDefaultReferrals($company);
        } catch (\Exception $e) {
            //handle Exception
        }
    }

    /**
     * Create base directories resource
     * @param $name String | name of the
     * @return false if operation failed.
     */
    private function createBaseDirectories($company)
    {
        $name = $company->name;
        $companyId = $company->id;
        $rootDirName = str_replace(' ', '_', strtolower($name)) . '_' . Carbon::now()->timestamp;
        $parentDir = $this->resourceService->createRootDir($rootDirName, $companyId);

        if (!$parentDir) {
            return false;
        }

        $baseDirectories = config('resources.BASE_DIRECTORIES');

        foreach ($baseDirectories as $dirName) {
            $this->resourceService->createDir($dirName, $parentDir->id, true);
        }

        $this->createSubscriberResources($company, $parentDir);
    }

    private function createSubscriberResources($company, $parentDir)
    {
        $resource = $this->resourceService->createDir(Resource::SUBSCRIBER_RESOURCES, $parentDir->id, true);
        CompanyMeta::create([
            'company_id' => $company->id,
            'key' => CompanyMeta::SUBSCRIBER_RESOURCE_ID,
            'value' => $resource->id,
        ]);
        return true;
    }

    private function createFinacialCategoroies($companyId)
    {
        $categories = config('jp.finacial_categories');
        foreach ($categories as $category) {
            FinancialCategory::create([
                'company_id' => $companyId,
                'name' => $category,
            ]);
        }
    }

    /**
     * Create ProductionBoard Columns
     * @param  int $companyId | Compnay Id
     * @return void
     */
    private function createProductionBoardColumns($companyId)
    {
        // get default column list..
        $pbConfig = config('jp.default_production_board');
        $pb = ProductionBoard::create([
            'company_id' => $companyId,
            'name' => $pbConfig['name'],
            'created_by' => 1,
        ]);

        foreach ($pbConfig['columns'] as $column) {
            ProductionBoardColumn::create([
                'company_id' => $companyId,
                'board_id' => $pb->id,
                'name' => $column,
                'default' => true,
                'created_by' => 1,
            ]);
        }
    }

    /**
     * Create Sample Customer
     * @param  Object $company Company
     * @return Void
     */
    public function createSampleData($company)
    {
        try {
            $context = App::make(\App\Services\Contexts\Context::class);
            $context->set($company);

            Auth::guard('web')->login($company->subscriber);

            $customerData = [
                'first_name' => 'Sample',
                'last_name' => 'Customer',
                'company_name' => 'Dummy',
                'rep_id'       => $company->subscriber->id,
                'billing' => [
                    'same_as_customer_address' => true
                ],
                'phones' => [
                    [
                        'label' => 'Phone',
                        'number' => '0000000000'
                    ]
                ],
                'address' => [
                    'address' => $company->office_address,
                    'city' => $company->office_city,
                    'state_id' => $company->office_state,
                    'country_id' => $company->office_country,
                    'zip' => $company->office_zip,
                ],
            ];

            $jobData = [
                'same_as_customer_address' => 1,
                'contact_same_as_customer' => 1,
                'trades' => [Trade::getOtherTradeId()],
                'description' => 'Sample job',
                'other_trade_type_description' => 'Other Trade type'
            ];

            $customer = $this->execute("App\Commands\CustomerCommand", ['input' => $customerData]);
            $jobData['customer_id'] = $customer->id;

            $job = $this->execute("App\Commands\JobCreateCommand", ['input' => $jobData]);
            Solr::jobIndex($job->id);

            Auth::logout();
        } catch (\Exception $e) {
            // handle exception..
        }
    }

    private function createClickthruPitch($companyId)
    {
        $data = [
            [
                'company_id' => $companyId,
                'manufacturer_id' => null,
                'name' => '<4',
                'fixed_amount' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ],
            [
                'company_id' => $companyId,
                'manufacturer_id' => null,
                'name' => '4-6',
                'fixed_amount' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ],
            [
                'company_id' => $companyId,
                'manufacturer_id' => null,
                'name' => '7-9',
                'fixed_amount' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ],
            [
                'company_id' => $companyId,
                'manufacturer_id' => null,
                'name' => '10-12',
                'fixed_amount' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ],
            [
                'company_id' => $companyId,
                'manufacturer_id' => null,
                'name' => '>12',
                'fixed_amount' => 0,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]
        ];

        $estimatePitch = EstimatePitch::insert($data);
    }

    private function createDefaultPaymentMethod($companyId)
    {
        $now = Carbon::now()->toDateTimeString();
        $defaultMethods = [
            [
                'label'         => "Cash",
                'method'        => 'cash',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Check",
                'method'        => 'echeque',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Credit Card",
                'method'        => 'cc',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Paypal",
                'method'        => 'paypal',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Other",
                'method'        => 'other',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Venmo",
                'method'        => 'venmo',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Zelle",
                'method'        => 'zelle',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "Digital Cash App",
                'method'        => 'Digital Cash App',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'label'         => "ACH/Online Payment",
                'method'        => 'ACH/Online Payment',
                'company_id'    => $companyId,
                'created_at'    => $now,
                'updated_at'    => $now
            ]
        ];

        PaymentMethod::insert($defaultMethods);
    }

    private function saveDefaultProposalViewer($company)
    {
        $now = Carbon::now()->toDateTimeString();
        $defaultProposalViewer = [
            [
                'title'  => 'What To Expect',
                'company_id' => $company->id,
                'is_active' => true,
                'display_order' => 1,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'title'  => 'Warranty Options',
                'company_id' => $company->id,
                'is_active' => true,
                'display_order' => 2,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'title'  => 'Financing Options' ,
                'company_id' => $company->id,
                'is_active' => true,
                'display_order' => 3,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'title'  => 'Testimonials' ,
                'company_id' => $company->id,
                'is_active' => true,
                'display_order' => 4,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
        ];

        ProposalViewer::insert($defaultProposalViewer);
    }

    private function saveDefaultTiers($company)
    {
        $now = Carbon::now()->toDateTimeString();
        $defaultTier = [
            [
                'name'  => 'Tier 1',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Tier 2',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Tier 3',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
        ];

        TierLibrary::insert($defaultTier);
    }

    private  function saveDefaultAppointmentResultOptions($companyId)
    {
       $data =  [
            [
                'name' => 'Not Home',
                'company_id' => $companyId,
                'fields' => json_encode([
                    [
                    'name' => 'Did you attempt to reach by phone?',
                    'type' => 'text',
                    ],
                ]),
                'created_by' => Auth::id(),
            ],
            [
                'name' => 'Not Interested',
                'company_id' => $companyId,
                'fields' => json_encode([
                    [
                        'name' => 'What was the reason?',
                        'type' => 'text',
                    ],
                ]),
                'created_by' => Auth::id(),
            ],
            [
                'name' => 'Interested',
                'company_id' => $companyId,
                'fields' => json_encode([[
                    'name' => 'Did you set next steps?',
                    'type' => 'text',
                ]]),
                'created_by' => Auth::id(),
            ],
            [
                'name' => 'Proposal in Hand',
                'company_id' => $companyId,
                'fields' => json_encode([
                    [
                        'name' => 'Did you set next steps?',
                        'type' => 'text',
                    ]
                ]),
                'created_by' => Auth::id(),
            ],
            [
                'name' => 'Job Sold',
                'company_id' => $companyId,
                'fields' => json_encode([
                    [
                        'name' => 'Did you push job to Job Awarded Stage?',
                        'type' => 'text',
                    ]
                ]),
                'created_by' => Auth::id(),
            ],
        ];

        AppointmentResultOption::insert($data);
    }

    private function saveDefaultReferrals($company)
    {
        $now = Carbon::now()->toDateTimeString();
        $defaultReferrals = [
            [
                'name'  => 'Website',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Google',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Facebook',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Truck Graphics',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Yard Signs',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
            [
                'name'  => 'Word of Mouth',
                'company_id' => $company->id,
                'created_at'    => $now,
                'updated_at'    => $now
            ],
        ];

        Referral::insert($defaultReferrals);
    }
}
