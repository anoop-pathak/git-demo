<?php
use Illuminate\Database\Seeder;
use App\Models\EVFileType;

class EVFileTypeTableSeeder extends Seeder
{

    public function run()
    {

        EVFileType::truncate();
        
        $types = [
            [
                'id'    => '2',
                'name' => 'Invoice'
            ],
            [
                'id'    => '3',
                'name' => 'PremiumReport'
            ],

            [
                'id'    => '4',
                'name' => 'NeighborhoodReport'
            ],
            [
                'id'    => '5',
                'name' => 'ExclusiveCover'
            ],
            [
                'id'    => '6',
                'name' => 'TopImage'
            ],
            [
                'id'    => '7',
                'name' => 'ParkingLotReport'
            ],
            [
                'id'   => '8',
                'name' => 'SolarReport'
            ],
            [
                'id'   => '12',
                'name' => 'PropertyOwner'
            ],
            [
                'id'   => '13',
                'name' => 'PreStandard'
            ],

            [
                'id'   => '17',
                'name' => 'ImagesOnly'
            ],
            [
                'id'   => '18',
                'name' => 'EagleViewXML'
            ],
            [
                'id'   => '22',
                'name' => 'NorthImage'
            ],
            [
                'id'   => '23',
                'name' => 'SouthImage'
            ],
            [
                'id'   => '24',
                'name' => 'EastImage'
            ],
            [
                'id'   => '25',
                'name' => 'WestImage'
            ],
            [
                'id'   => '28',
                'name' => 'Length Diagram'
            ],
            [
                'id'   => '29',
                'name' => 'Notes Diagram'
            ],
            [
                'id'   => '31',
                'name' => 'Pitch Diagram'
            ],
            [
                'id'   => '32',
                'name' => 'Area Diagram'
            ],
            [
                'id'   => '42',
                'name' => 'Detailed Length Diagram'
            ],
            [
                'id'   => '43',
                'name' => 'Notes NoLabel Diagram'
            ],
            [
                'id'   => '51',
                'name' => 'Claims Ready Report'
            ],
            [
                'id'   => '52',
                'name' => 'AA3D Report'
            ],

            [
                'id'   => '53',
                'name' => 'AA2D Report'
            ],
            [
                'id'   => '71',
                'name' => 'EC3D Report'
            ],
            [
                'id'   => '73',
                'name' => 'EC2D Report'
            ],
            [
                'id'   => '75',
                'name' => 'ECPremium Report'
            ],
            [
                'id'   => '76',
                'name' => 'PremiumAW Report'
            ],

            [
                'id'   => '77',
                'name' => 'Walls AddOn'
            ],
            [
                'id'   => '79',
                'name' => 'Wall Area Diagram'
            ],
            [
                'id'   => '80',
                'name' => 'Alternate Wall View'
            ],
            [
                'id'   => '81',
                'name' => 'Missing Wall Diagram'
            ],
            [
                'id'   => '82',
                'name' => 'North Elevation Diagram'
            ],
            [
                'id'   => '83',
                'name' => 'South Elevation Diagram'
            ],
            [
                'id'   => '84',
                'name' => 'East Elevation Diagram'
            ],
            [
                'id'   => '85',
                'name' => 'West Elevation Diagram'
            ],
            [
                'id'   => '86',
                'name' => 'Roof Penetration Diagram'
            ],
            [
                'id'   => '87',
                'name' => 'Walls Only Report'
            ],
            [
                'id'   => '88',
                'name' => 'WallsOnlyMetadata'
            ],
            [
                'id'   => '89',
                'name' => 'Risk Management Metadata'
            ],
            [
                'id'   => '90',
                'name' => 'LivingAreaDiagram'
            ],
            [
                'id'   => '91',
                'name' => 'WallsNoLabelsDiagram'
            ],
            [
                'id'   => '92',
                'name' => 'Area View Image'
            ],
            [
                'id'   => '93',
                'name' => 'Pitch Roof Penetration Diagram'
            ],
            [
                'id'   => '94',
                'name' => 'BldgFootprintDiagram'
            ],
            [
                'id'   => '95',
                'name' => 'SiteMapDiagram'
            ],
            [
                'id'   => '96',
                'name' => 'OutbuildingDiagram'
            ],
            [
                'id'   => '98',
                'name' => 'Roof Geometry Report'
            ],
            [
                'id'   => '105',
                'name' => 'QuickSquares Report'
            ],
            [
                'id'   => '107',
                'name' => 'EV Measurement JSON'
            ],
            [
                'id'   => '109',
                'name' => 'Gutter Report'
            ],
            [
                'id'   => '111',
                'name' => 'Diagram Report'
            ],
            [
                'id'   => '116',
                'name' => 'QuickSquares EC Report'
            ],
            [
                'id'   => '121',
                'name' => 'WallsLite Report'
            ]

        ];
        EVFileType::insert($types);
    }
}
