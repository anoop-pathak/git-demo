<?php
use Illuminate\Database\Seeder;

class OnboardChecklistSectionsTableSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        OnboardChecklistSection::truncate();
        OnboardChecklist::truncate();

        $firstSection = [
            'title'     => 'Getting Started',
            'position'  => 1
        ];

        $secondSection = [
            'title'     => 'Customers and Jobs',
            'position'  => 2
        ];

        $fourthSection = [
            'title'     => 'Financials',
            'position'  => 3
        ];

        $fifthSection = [
            'title'     => 'Production',
            'position'  => 4
        ];

        $sixSection = [
            'title'     => 'Additional Resources',
            'position'  => 5
        ];
                
        $section1 = OnboardChecklistSection::create($firstSection);
        $section2 = OnboardChecklistSection::create($secondSection);
        $section4 = OnboardChecklistSection::create($fourthSection);
        $section5 = OnboardChecklistSection::create($fifthSection);
        $section6 = OnboardChecklistSection::create($sixSection);

        $checklists = [
            [
                'title'       => 'Download JOBPROGRESS iOS or Android App',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section1->id
            ],
            [
                'title'       => 'Upload your Company Information and Logo',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section1->id
            ],
            [
                'title'       => 'Customize your Company\'s Workflow',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section1->id
            ],
            [
                'title'       => 'Add users to JOBPROGRESS',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section1->id
            ],
            [
                'title'       => 'Connect Google Account',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section1->id
            ],
            [
                'title'       => 'Enter or Import your Customers and Leads',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section2->id
            ],
            [
                'title'       => 'Create or Order Proposal and Estimate Templates',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section2->id
            ],
            [
                'title'       => 'Select your Company\'s Trade and Work types',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section2->id
            ],
            [
                'title'       => 'Add Company Divisions (If applicable)',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section2->id
            ],
            [
                'title'       => 'Create Email Templates',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section2->id
            ],
            [
                'title'       => 'Create Financial Macros',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section4->id
            ],
            [
                'title'       => 'Choose the "Job Awarded" Stage and connect to QuickBooks Online, if applicable',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section4->id
            ],
            [
                'title'       => 'Add state tax rate',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section4->id
            ],
            [
                'title'       => 'Add Operating Country and States',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section4->id
            ],
            [
                'title'       => 'Add Timezone',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section4->id
            ],
            [
                'title'       => 'Customize Your Production Board ',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section5->id
            ],
            [
                'title'       => 'Upload Important Company Files',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section5->id
            ],
            [
                'title'       => 'Add labor and subcontractor resources',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section5->id
            ],
            [
                'title'       => 'Enter Company Contacts (Businesses and Individual Phone Numbers)',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section5->id
            ],
            [
                'title'       => 'Add Referral Sources',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section5->id
            ],
            [
                'title'       => 'Connect Eagle View Accounts (If applicable)',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section6->id
            ],
            [
                'title'       => 'Create Company Directories for Files and Important Documents',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section6->id
            ],
            [
                'title'       => 'Upload or Manually Enter Your Materials and Pricing Library',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section6->id
            ],
            [
                'title'       => 'Add Resources to the Resource Viewer ',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section6->id
            ],
            [
                'title'       => 'Enable Sales Automation ',
                'action'      => null,
                'video_url'   => null,
                'is_required' => false,
                'section_id'  => $section6->id
            ]
        ];

        //Save Checklist
        foreach ($checklists as $checklist) {
            OnboardChecklist::create($checklist);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
