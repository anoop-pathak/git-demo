<?php 
return [

	// system attributes
	'system' => [
		[
			'name'			 => 'Name',
			'locked'		 => true,
			'sub_attributes' => [],
		],
	],

	'All' => [
		[
			'name'			 => 'Linear ft',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Square ft',
			'locked'		 => false,
			'sub_attributes' => [],
		],
	],

	// ROOFING
	8 => [
		[
			'name'			 => 'Facets',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Pitch',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Ridges',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Hips',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Valleys',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Rakes',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Eaves',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Flashing',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Step Flashing',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Waste Factor',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Squares',
			'locked'		 => true,
			'sub_attributes' => [],
		],
	],

	// SIDING
	9 => [
		[
			'name'			 => 'Linear ft',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Square ft',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Area',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Shutters',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Vents',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Area With Waste Factor Calculation',
			'locked'		 => true,
			'sub_attributes' => [
				[
					'name'			 => 'Zero',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Plus 10 Percent',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Plus 18 Percent',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'With Openings',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Openings Plus 10 Percent',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Openings Plus 18 Percent',
					'locked'		 => true,
					'sub_attributes' => [],
				],
			],
		],
		[
			'name'			 => 'Trim',
			'locked'		 => true,
			'sub_attributes' => [
				[
					'name'			 => 'Level Starter',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Sloped',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Vertical',
					'locked'		 => true,
					'sub_attributes' => [],
				],
			],
		],
		[
			'name'			 => 'Corners',
			'locked'		 => true,
			'sub_attributes' => [
				[
					'name'			 => 'Inside Number',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Inside Length',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Outside Number',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Outside Length',
					'locked'		 => true,
					'sub_attributes' => [],
				],
			],
		],
		[
			'name'			 => 'Roofline',
			'locked'		 => true,
			'sub_attributes' => [
				[
					'name'			 => 'Level Frieze Board',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Sloped Frieze Board',
					'locked'		 => true,
					'sub_attributes' => [],
				],
			],
		],
		[
			'name'			 => 'Openings',
			'locked'		 => true,
			'sub_attributes' => [
				[
					'name'			 => 'Tops',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Sills',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Sides',
					'locked'		 => true,
					'sub_attributes' => [],
				],
				[
					'name'			 => 'Openings Total',
					'locked'		 => true,
					'sub_attributes' => [],
				],
			],
		],
	],

	// WINDOWS
	11 => [
		[
			'name'			 => 'United Inches',
			'locked'		 => true,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Area',
			'locked'		 => true,
			'sub_attributes' => [],
		],
	],

	// PAINTING
	13 => [
		[
			'name'			 => 'Length (ft)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Width (ft)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Ceiling Height (ft)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Windows (#)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Doors (#)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Prep Hours (hrs)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Setup & Cleanup Hours (hrs)',
			'locked'		 => false,
			'sub_attributes' => [],
		],
		[
			'name'			 => 'Square ft',
			'locked'		 => false,
			'sub_attributes' => [],
		],
	],

	// DOORS
	37 => [
		[
			'name'			 => 'Area',
			'locked'		 => true,
			'sub_attributes' => [],
		],
	],
];