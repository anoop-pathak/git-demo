<?php
use Illuminate\Database\Seeder;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;

class TradeTableSeeder extends Seeder
{

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Trade::truncate();

        $trades = ['CARPENTRY', 'ELECTRICIAN', 'HARDSCAPING', 'LANDSCAPING', 'LOCKSMITH', 'MASONRY & CONCRETE', 'PLUMBING', 'ROOFING', 'SIDING', 'GUTTERS', 'WINDOWS', 'HVAC', 'PAINTING', 'POOLS', 'HANDYMAN', 'PAVING', 'TREE SERVICE', 'SECURITY', 'CARPETS & FLOORING', 'CLEANING & JANITORIAL', 'FENCING', 'GENERAL CONTRACTING', 'HOME REMODELING & IMPROVEMENTS', 'OTHER', 'SOLAR', 'CUSTOM BUILDER', 'PEST CONTROL', 'RESTORATION SERVICES', 'AWNINGS & CANOPIES', 'FIRE PROTECTION', 'AUTOMOTIVE', 'WATER TREATMENT', 'DEMOLITION SERVICES', 'REAL ESTATE', 'PROPERTY MANAGEMENT', 'HOME SERVICES', 'DOORS', 'FOUNDATIONS', 'GRAPHICS & PRINTING', 'EXCAVATION', 'WASTEWATER TREATMENT', 'LIGHTING', 'TILE & STONE', 'TRANSPORTATION', 'REMEDIATION', 'WATER DAMAGE SERVICES', 'INSULATION', 'DECKING', 'SANITATION', 'CHIMNEY SERVICES', 'SCAFFOLDING', 'SOFFIT, FASCIA & TRIM', 'SPECIALTY STRUCTURES', 'UPHOLSTERY SERVICES', 'CEILINGS', 'CONSULTATION SERVICES ', 'AIR QUALITY SERVICES', 'HAZARDOUS CLEANUP', 'DRYWALL', 'APPLIANCES ', 'BACKSPLASHES', 'BATH FIXTURES', 'CABINETRY', 'COUNTERTOPS ', 'CONCRETE ', 'DRIVEWAY', 'ELECTRICAL ', 'EXTERIOR DOORS ', 'FAUCETS & SINKS ', 'FLOORING ', 'GARAGE DOORS ', 'LAWN, GARDEN & LANDSCAPING', 'MOLDING, TRIM & FINISH CARPENTRY', 'PAVERS', 'PATIO & POOL ', 'STRUCTURES - EXTERIOR', 'WALKWAYS', 'WATER HEATING & FILTRATION ', 'WALLS - HARDSCAPING ', 'WINDOW TREATMENT ', 'INTERIOR REMODELING'];

        foreach ($trades as $key => $value) {
            Trade::create([
                'name' => $value
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
