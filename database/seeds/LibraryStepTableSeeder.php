<?php
use Illuminate\Database\Seeder;
use App\Models\LibraryStep;

class LibraryStepTableSeeder extends Seeder
{

    public function run()
    {
        LibraryStep::truncate();

        $librarySteps = [
            [
                'name' => 'Estimation',
                'code' => 'estimation',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Estimation\EstimationServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Demo1',
                'code' => 'demo1',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Demo1\Demo1ServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 1,
            ],
            [
                'name' => 'Custom',
                'code' => 'custom',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Custom\CustomServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 1,
            ],
            [
                'name' => 'Proposals & Contracts',
                'code' => 'proposals_contracts',
                'service_provider_class' => 'JobProgress\Workflow\Steps\ProposalsContracts\ProposalsContractsServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Resource Viewer',
                'code' => 'resource_viewer',
                'service_provider_class' => 'JobProgress\Workflow\Steps\ResourceViewer\ResourceViewerServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Materials List',
                'code' => 'materials_list',
                'service_provider_class' => 'JobProgress\Workflow\Steps\MaterialsList\MaterialsListServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Warranties',
                'code' => 'warranties',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Warranties\WarrantiesServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Deposits',
                'code' => 'deposits',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Deposits\DepositsServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Permits',
                'code' => 'permits',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Permits\PermitsServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Financing',
                'code' => 'financing',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Financing\FinancingServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ],
            [
                'name' => 'Payments',
                'code' => 'payments',
                'service_provider_class' => 'JobProgress\Workflow\Steps\Payments\PaymentsServiceProvider',
                'description' => null,
                'status' => 1,
                'is_custom' => 0,
            ]
        ];
        
        foreach ($librarySteps as $key => $value) {
            LibraryStep::create($value);
        }
    }
}
