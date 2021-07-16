(function(){

	/**
	*
	* @get Blair for Templates
	* @param [ci COMPANY_ID]
	* @param [ti TEMPLATE_ID]
	* @param [t2i TEMPLATE2_ID]
	* @param [pti PRODUCT_TEMPLATE_ID]
	* @param [t3i TEMPLATE3_ID]
	*/
	var getBlairTemplateSameDetails = function(ci, ti, t2i, pti, t3i) {
		return {
			SELECTION_CONTAINER: 'blair-dropdown-options',
			PRODUCTS : {
				CONTAINER: 'blair-product-container',
				SUB: 'sub-blair-product-container',
				PRODUCT_PRICE: 'blair-product-price',
				PRODUCT_PROMO: 'blair-product-promo',
				PRODUCT_TOTAL: 'blair-product-total',
				PRODUCT_MONTHS: 'blair-product-months',
				PRODUCT_PER_MONTH:'blair-product-per-month',
				PRODUCT_NAME_CONTAINER: 'blair-product-name-container',
				PRODUCT_NAME: 'blair-product-name',
				PRODUCT_NAME_PRICE: 'blair-product-name-price'
			},
			COMPANY_ID: ci,
			TEMPLATE_ID: JP.getArray(ti),
			TEMPLATE_2: JP.getArray(t2i),
			PRODUCT_TEMPLATE_ID: JP.getArray(pti),
			TEMPLATE_3: JP.getArray(t3i)
		};
	};

	var local = function() {

		var claimsPor = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		claimsPor.TOPSIDE_TEMPLATE_ID = [];
		claimsPor.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		claimsPor.BASE_PROPOSAL = [6284];
		claimsPor.OPTION_TEMPLATES = [6285];


		var heartland = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		heartland.TOPSIDE_TEMPLATE_ID = [];
		heartland.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		heartland.BASE_PROPOSAL = [6284];
		heartland.OPTION_TEMPLATES = [6285];

		var roofTech = getBlairTemplateSameDetails(900, [], [], null, null);

		roofTech.TOPSIDE_TEMPLATE_ID = [12691];
		roofTech.TOPSIDE_GBB_TEMPLATE = [12692];
		roofTech.BASE_PROPOSAL = [12695];
		roofTech.OPTION_TEMPLATES = [12696];

		var hudsonContracting = getBlairTemplateSameDetails(1, [], [], null, null);

		hudsonContracting.TOPSIDE_TEMPLATE_ID = [1];
		hudsonContracting.TOPSIDE_GBB_TEMPLATE = [1];
		hudsonContracting.BASE_PROPOSAL = [1];
		hudsonContracting.OPTION_TEMPLATES = [1];

		return {
			API_PATH: window.location.origin,
			ARCHTEN: {
				COMPANY_ID: 311,
				ROOFING_OPTION_TEMPLATES: [4047],
				ADDITIONAL_OPTION_TEMPLATES: [4049]
			},

	        QUINN: {
				COMPANY_ID: 267,
				TEMPLATE_ID: [],
				GBB_TEMPLATE: [5368, 5385]
			},
			TOPSIDE: {
				COMPANY_ID: 370,
				TEMPLATE_ID: [4138, 4403, 4406],
				GBB_TEMPLATE: [4399, 4407, 4241, 4404, 4320, 4544, 4402, 4750, 4750, 4759]
			},
			BLAIR: getBlairTemplateSameDetails(12, [2, 5], [1, 4], null, null),
			PINNACLE: getBlairTemplateSameDetails(481, [5494], [5493], null, null),
			DOUBLE_D_CONTRACTORS: getBlairTemplateSameDetails(1141, [2, 5], [1, 4], null, null),
			V_NANFITO_ROOFING: getBlairTemplateSameDetails(14, [2, 5], [1, 4], null, null),
			AB_EDWARD_ENTERPRISES: getBlairTemplateSameDetails(122, [2, 5], [1, 4], null, null),
			SOLAR_ME: getBlairTemplateSameDetails(122, [2, 5], [1, 4], null, null),
			DANIEL_HOOD_ROOFING: getBlairTemplateSameDetails(155, [2, 5], [1, 4], null, null),
			FIRST_CLASS_ROOFING: getBlairTemplateSameDetails(15556, [2, 5], [1, 4], null, null),
			AMERICAN_ROOFING: getBlairTemplateSameDetails(1, [2, 5], [1, 4], null, null),
			JMAC: getBlairTemplateSameDetails(12, [6017], [6016], null, null),
			LINKVILLE_ROOFING: getBlairTemplateSameDetails(12, [6014], [6013], null, null),
			PINNACLE_REMODELLING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			WYOMING_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			CLAIMS_PRO_RESTORATION: claimsPor,
			AFFORDABLE_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			GREAT_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			BURELL_BUILT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			JHON_HENDERSON_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ALLIED_CONSTRUCTION_AND_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			MIDSOUTH_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FLORES_AND_COONEY_DEVELOPMENT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			HEARTLAND: heartland,
			ROOF_TECH: roofTech,
			HUDSON_CONTRACTING: hudsonContracting,
			THOROUGHBREB_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			GEORGE_KELLER: getBlairTemplateSameDetails(1, [2], [1], null, null),
			NORTH_WOOD_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			KEITH_GAUVIN_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			VERSATILE_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			SPARROW_ERTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DIOR_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FIVE_BORO_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			G_B_HOME_IMPROVEMENTS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DYNAMIC_HOME_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ROOFING_RIGHT_LLC:  getBlairTemplateSameDetails(1, [2], [1], null, null),
			COMMONWEALTH_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			RESIDENTIAL_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_H_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			STONE_HEATING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_EUGENE: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ENVISION_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_M_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRO_BUILT_HOMES: getBlairTemplateSameDetails(1, [2], [1], null, null)
		}
	};

	var staging = function() {

		var claimsPor = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		claimsPor.TOPSIDE_TEMPLATE_ID = [];
		claimsPor.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		claimsPor.BASE_PROPOSAL = [6284];
		claimsPor.OPTION_TEMPLATES = [6285];

		var heartland = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		heartland.TOPSIDE_TEMPLATE_ID = [];
		heartland.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		heartland.BASE_PROPOSAL = [6284];
		heartland.OPTION_TEMPLATES = [6285];

		var roofTech = getBlairTemplateSameDetails(12, [2], [1], null, null);

		roofTech.TOPSIDE_TEMPLATE_ID = [];
		roofTech.TOPSIDE_GBB_TEMPLATE = [1, 2];
		roofTech.BASE_PROPOSAL = [1];
		roofTech.OPTION_TEMPLATES = [2];

		var hudsonContracting = getBlairTemplateSameDetails(1, [], [], null, null);

		hudsonContracting.TOPSIDE_TEMPLATE_ID = [1];
		hudsonContracting.TOPSIDE_GBB_TEMPLATE = [1];
		hudsonContracting.BASE_PROPOSAL = [1];
		hudsonContracting.OPTION_TEMPLATES = [1];

		return {
			API_PATH: window.location.origin + '/api/public',
			ARCHTEN: {
				COMPANY_ID: 311,
				ROOFING_OPTION_TEMPLATES: [4047],
				ADDITIONAL_OPTION_TEMPLATES: [4049]
			},
	        QUINN: {
				COMPANY_ID: 267,
				TEMPLATE_ID: [],
				GBB_TEMPLATE: [5368, 5385]
			},
			TOPSIDE: {
				COMPANY_ID: 370,
				TEMPLATE_ID: [4138, 4403, 4406],
				GBB_TEMPLATE: [4399, 4407, 4241, 4404, 4320, 4544, 4402, 4750, 4750, 4759]
			},
			BLAIR: getBlairTemplateSameDetails(217, [137, 140], [138, 141], null, null),
	        PINNACLE: getBlairTemplateSameDetails(481, [5494], [5493], null, null),
	        DOUBLE_D_CONTRACTORS: getBlairTemplateSameDetails(12, [5977], [5976], null, null),
			V_NANFITO_ROOFING: getBlairTemplateSameDetails(121, [5974], [5973], null, null),
 			AB_EDWARD_ENTERPRISES: getBlairTemplateSameDetails(1225, [5971], [5970], null, null),
			SOLAR_ME: getBlairTemplateSameDetails(1252, [5968], [5967], null, null),
			DANIEL_HOOD_ROOFING: getBlairTemplateSameDetails(12112, [5964], [5961], null, null),
			FIRST_CLASS_ROOFING: getBlairTemplateSameDetails(12, [5962], [5960], null, null),
			AMERICAN_ROOFING: getBlairTemplateSameDetails(12, [5954], [5953], null, null),
			JMAC: getBlairTemplateSameDetails(12, [6017], [6016], null, null),
			LINKVILLE_ROOFING: getBlairTemplateSameDetails(12, [6014], [6013], null, null),
			PINNACLE_REMODELLING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			WYOMING_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			CLAIMS_PRO_RESTORATION: claimsPor,
			AFFORDABLE_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			GREAT_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			BURELL_BUILT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			JHON_HENDERSON_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ALLIED_CONSTRUCTION_AND_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			MIDSOUTH_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FLORES_AND_COONEY_DEVELOPMENT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			HEARTLAND: heartland,
			ROOF_TECH: roofTech,
			HUDSON_CONTRACTING: hudsonContracting,
			THOROUGHBREB_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			GEORGE_KELLER: getBlairTemplateSameDetails(1, [2], [1], null, null),
			NORTH_WOOD_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			KEITH_GAUVIN_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			VERSATILE_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			SPARROW_ERTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DIOR_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FIVE_BORO_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			G_B_HOME_IMPROVEMENTS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DYNAMIC_HOME_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ROOFING_RIGHT_LLC:  getBlairTemplateSameDetails(1, [2], [1], null, null),
			COMMONWEALTH_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			RESIDENTIAL_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_H_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			STONE_HEATING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_EUGENE: getBlairTemplateSameDetails(1, [2], [1], null, null) ,
			ENVISION_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_M_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRO_BUILT_HOMES: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ENVISION_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_M_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRO_BUILT_HOMES: getBlairTemplateSameDetails(1, [2], [1], null, null)
		}
	};

	var qa = function() {

		var claimsPor = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		claimsPor.TOPSIDE_TEMPLATE_ID = [];
		claimsPor.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		claimsPor.BASE_PROPOSAL = [6284];
		claimsPor.OPTION_TEMPLATES = [6285];

		var heartland = getBlairTemplateSameDetails(12, [5958], [5957], null, null);

		heartland.TOPSIDE_TEMPLATE_ID = [];
		heartland.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		heartland.BASE_PROPOSAL = [6284];
		heartland.OPTION_TEMPLATES = [6285];

		var roofTech = getBlairTemplateSameDetails(12, [2], [1], null, null);

		roofTech.TOPSIDE_TEMPLATE_ID = [];
		roofTech.TOPSIDE_GBB_TEMPLATE = [1, 2];
		roofTech.BASE_PROPOSAL = [1];
		roofTech.OPTION_TEMPLATES = [2];

		var hudsonContracting = getBlairTemplateSameDetails(1, [], [], null, null);

		hudsonContracting.TOPSIDE_TEMPLATE_ID = [1];
		hudsonContracting.TOPSIDE_GBB_TEMPLATE = [1];
		hudsonContracting.BASE_PROPOSAL = [1];
		hudsonContracting.OPTION_TEMPLATES = [1];

		return {
			API_PATH: window.location.origin + '/api/public',
			ARCHTEN: {
				COMPANY_ID: 311,
				ROOFING_OPTION_TEMPLATES: [4047],
				ADDITIONAL_OPTION_TEMPLATES: [4049]
			},
	        QUINN: {
				COMPANY_ID: 267,
				TEMPLATE_ID: [],
				GBB_TEMPLATE: [5368, 5385]
			},
			TOPSIDE: {
				COMPANY_ID: 370,
				TEMPLATE_ID: [4138, 4403, 4406],
				GBB_TEMPLATE: [4399, 4407, 4241, 4404, 4320, 4544, 4402, 4750, 4750, 4759]
			},
			BLAIR: getBlairTemplateSameDetails(217, [137, 140], [138, 141], null, null),
	        PINNACLE: getBlairTemplateSameDetails(481, [5494], [5493], null, null),
	        DOUBLE_D_CONTRACTORS: getBlairTemplateSameDetails(12, [5977], [5976], null, null),
			V_NANFITO_ROOFING: getBlairTemplateSameDetails(121, [5974], [5973], null, null),
			AB_EDWARD_ENTERPRISES: getBlairTemplateSameDetails(1225, [5971], [5970], null, null),
			SOLAR_ME: getBlairTemplateSameDetails(1252, [5968], [5967], null, null),
			DANIEL_HOOD_ROOFING: getBlairTemplateSameDetails(12112, [5964], [5961], null, null),
			FIRST_CLASS_ROOFING: getBlairTemplateSameDetails(12, [5962], [5960], null, null),
			AMERICAN_ROOFING: getBlairTemplateSameDetails(12, [5954], [5953], null, null),
			JMAC: getBlairTemplateSameDetails(12, [6017], [6016], null, null),
			LINKVILLE_ROOFING: getBlairTemplateSameDetails(12, [6014], [6013], null, null),
			PINNACLE_REMODELLING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			WYOMING_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			CLAIMS_PRO_RESTORATION: claimsPor,
			AFFORDABLE_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			GREAT_ROOFING: getBlairTemplateSameDetails(12, [5958], [5957], null, null),
			BURELL_BUILT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			JHON_HENDERSON_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ALLIED_CONSTRUCTION_AND_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			MIDSOUTH_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FLORES_AND_COONEY_DEVELOPMENT: getBlairTemplateSameDetails(1, [2], [1], null, null),
			HEARTLAND: heartland,
			ROOF_TECH: roofTech,
			HUDSON_CONTRACTING: hudsonContracting,
			THOROUGHBREB_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			GEORGE_KELLER: getBlairTemplateSameDetails(1, [2], [1], null, null),
			NORTH_WOOD_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			KEITH_GAUVIN_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			VERSATILE_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			SPARROW_ERTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DIOR_CONSTRUCTION: getBlairTemplateSameDetails(1, [2], [1], null, null),
			FIVE_BORO_REMODELING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			G_B_HOME_IMPROVEMENTS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			DYNAMIC_HOME_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ROOFING_RIGHT_LLC:  getBlairTemplateSameDetails(1, [2], [1], null, null),
			COMMONWEALTH_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			RESIDENTIAL_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_H_EXTERIORS: getBlairTemplateSameDetails(1, [2], [1], null, null),
			STONE_HEATING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_INC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRESSURE_POINT_ROOFING_EUGENE: getBlairTemplateSameDetails(1, [2], [1], null, null),
			ENVISION_ROOFING_LLC: getBlairTemplateSameDetails(1, [2], [1], null, null),
			A_AND_M_ROOFING: getBlairTemplateSameDetails(1, [2], [1], null, null),
			PRO_BUILT_HOMES: getBlairTemplateSameDetails(1, [2], [1], null, null)
		}
	};

	var live = function() {

		var claimsPor = getBlairTemplateSameDetails(179, [6260], [6259], null, null);
		claimsPor.TOPSIDE_TEMPLATE_ID = [];
		claimsPor.TOPSIDE_GBB_TEMPLATE = [6280, 6281];
		claimsPor.BASE_PROPOSAL = [6284];
		claimsPor.OPTION_TEMPLATES = [6285];


		var heartland = getBlairTemplateSameDetails(640, [7674], [7673], null, null);
		heartland.TOPSIDE_TEMPLATE_ID = [];
		heartland.TOPSIDE_GBB_TEMPLATE = [7933, 7934];
		heartland.BASE_PROPOSAL = [7937];
		heartland.OPTION_TEMPLATES = [7938];

		var roofTech = getBlairTemplateSameDetails(12, [2], [1], null, null);

		roofTech.TOPSIDE_TEMPLATE_ID = [];
		roofTech.TOPSIDE_GBB_TEMPLATE = [1, 2];
		roofTech.BASE_PROPOSAL = [1];
		roofTech.OPTION_TEMPLATES = [2];

		var hudsonContracting = getBlairTemplateSameDetails(1197, [], [], null, null);

		// TOPSIDE
		hudsonContracting.TOPSIDE_TEMPLATE_ID = [];
		hudsonContracting.TOPSIDE_GBB_TEMPLATE = [12966, 12967];

		// SEASHORE
		hudsonContracting.BASE_PROPOSAL = [12970];
		hudsonContracting.OPTION_TEMPLATES = [12971];

		return {
			API_PATH: window.location.origin + '/api/public',
			ARCHTEN: {
				COMPANY_ID: 311,
				ROOFING_OPTION_TEMPLATES: [4047],
				ADDITIONAL_OPTION_TEMPLATES: [4049]
			},
	        QUINN: {
				COMPANY_ID: 267,
				TEMPLATE_ID: [],
				GBB_TEMPLATE: [5368, 5385]
			},
			TOPSIDE: {
				COMPANY_ID: 370,
				TEMPLATE_ID: [4138, 4403, 4406],
				GBB_TEMPLATE: [4399, 4407, 4241, 4404, 4320, 4544, 4402, 4750, 4750, 4759]
			},
			BLAIR: getBlairTemplateSameDetails(309, [3867, 4426, 5882], [4425, 5881], [3859], [4632]),
	        PINNACLE: getBlairTemplateSameDetails(481, [5494], [5493], null, null),
			DOUBLE_D_CONTRACTORS: getBlairTemplateSameDetails(11, [5977], [5976], null, null),
			V_NANFITO_ROOFING: getBlairTemplateSameDetails(269, [5974], [5973], null, null),
			AB_EDWARD_ENTERPRISES: getBlairTemplateSameDetails(307, [5971], [5970], null, null),
			SOLAR_ME: getBlairTemplateSameDetails(541, [5968], [5967], null, null),
			DANIEL_HOOD_ROOFING: getBlairTemplateSameDetails(390, [5964], [5961], null, null),
			FIRST_CLASS_ROOFING: getBlairTemplateSameDetails(177, [5962], [5960], null, null),
			AMERICAN_ROOFING: getBlairTemplateSameDetails(547, [5954], [5953], null, null),
			JMAC: getBlairTemplateSameDetails(158, [6017], [6016], null, null),
			LINKVILLE_ROOFING: getBlairTemplateSameDetails(484, [6014], [6013], null, null),
			PINNACLE_REMODELLING: getBlairTemplateSameDetails(548, [5958], [5957], null, null),
			WYOMING_ROOFING: getBlairTemplateSameDetails(495, [6045], [6044], null, null),
			CLAIMS_PRO_RESTORATION: claimsPor,
			AFFORDABLE_ROOFING: getBlairTemplateSameDetails(600, [6457], [6456], null, null),
			GREAT_ROOFING: getBlairTemplateSameDetails(352, [6983], [6982], null, null),
			BURELL_BUILT: getBlairTemplateSameDetails(589, [7091], [7090], null, null),
			JHON_HENDERSON_CONSTRUCTION: getBlairTemplateSameDetails(264, [7509], [7508], null, null),
			ALLIED_CONSTRUCTION_AND_REMODELING: getBlairTemplateSameDetails(667, [7326], [7325], null, null),
			MIDSOUTH_CONSTRUCTION: getBlairTemplateSameDetails(674, [7506], [7505], null, null),
			FLORES_AND_COONEY_DEVELOPMENT: getBlairTemplateSameDetails(670, [7644], [7643], null, null),
			HEARTLAND: heartland,
			ROOF_TECH: roofTech,
			HUDSON_CONTRACTING: hudsonContracting,
			THOROUGHBREB_ROOFING: getBlairTemplateSameDetails(669, [8190], [8189], null, null),
			GEORGE_KELLER: getBlairTemplateSameDetails(105, [8391], [8390], null, null),
			NORTH_WOOD_ROOFING: getBlairTemplateSameDetails(766, [8793], [8792], null, null),
			KEITH_GAUVIN_ROOFING: getBlairTemplateSameDetails(254, [8797], [8796], null, null),
			VERSATILE_ROOFING: getBlairTemplateSameDetails(688, [8741], [8740], null, null),
			SPARROW_ERTERIORS: getBlairTemplateSameDetails(715, [8744], [8743], null, null),
			DIOR_CONSTRUCTION: getBlairTemplateSameDetails(77, [8862], [8861], null, null),
			FIVE_BORO_REMODELING: getBlairTemplateSameDetails(317, [8859], [8858], null, null),
			G_B_HOME_IMPROVEMENTS: getBlairTemplateSameDetails(803, [8925], [8924], null, null),
			DYNAMIC_HOME_EXTERIORS: getBlairTemplateSameDetails(808, [8945], [8944], null, null),
			ROOFING_RIGHT_LLC:  getBlairTemplateSameDetails(827, [9426], [9425], null, null),
			COMMONWEALTH_ROOFING_LLC: getBlairTemplateSameDetails(824, [10059], [10058], null, null),
			RESIDENTIAL_ROOFING_LLC: getBlairTemplateSameDetails(646, [10201], [10200], null, null),
			A_AND_H_EXTERIORS: getBlairTemplateSameDetails(868, [10171], [10170], null, null),
			STONE_HEATING_INC: getBlairTemplateSameDetails(878, [10303], [10302], null, null),
			PRESSURE_POINT_ROOFING_INC: getBlairTemplateSameDetails(876, [10306], [10305], null, null),
			PRESSURE_POINT_ROOFING_EUGENE: getBlairTemplateSameDetails(877, [10309], [10308], null, null),
			ENVISION_ROOFING_LLC: getBlairTemplateSameDetails(971, [11094], [11093], null, null),
			A_AND_M_ROOFING: getBlairTemplateSameDetails(956, [10926], [10925], null, null),
			PRO_BUILT_HOMES: getBlairTemplateSameDetails(983, [11285], [11284], null, null)
		}
	};

	/*********************************************************
						LOAD ENV
	**********************************************************/

	var liveEnv = function() {

		window.GS_DEALER 		= "81009646";
	   	window.GS_BASE_PLAN 	= "4063";
	   	window.GS_PROGRAM 		= "GreenSky Consumer Projects";
	   	window.GS_PROMO 		= "";
	   	window.GS_API_KEY 		= "Sm9iUHJvZ3Jlc3M6RUI2ZE1FUkxzTGNCY1UycA==";
	   	window.GS_EXPERIENCE 	= 1;
	   	window.GS_ENV 			= 0;
   }

	var stageEnv = function() {

		window.GS_DEALER 		= "81009646";
		window.GS_BASE_PLAN 	= "4063";
		window.GS_PROGRAM 		= "GreenSky Consumer Projects";
		window.GS_PROMO 		= "";
		window.GS_API_KEY 		= "bWVyY2hhbnQxMDI6bWVyY2hhbnQxMDI=";
		window.GS_EXPERIENCE 	= 1;
		window.GS_ENV 			= 1;
   }

	var defaultEnv = function() {
	   window.GS_DEALER 		= "81009646";
	   window.GS_BASE_PLAN 		= "4063";
	   window.GS_PROGRAM 		= "GreenSky Consumer Projects";
	   window.GS_PROMO 			= "";
	   window.GS_API_KEY 		= "bWVyY2hhbnQxMDI6bWVyY2hhbnQxMDI=";
	   window.GS_EXPERIENCE 	= 1;
	   window.GS_ENV 			= 1;
   }

	/*********************************************************
					   END LOAD ENV
   **********************************************************/

	window.JPAPP = {
		getConfig: function() {

			// Live Server
			if( window.location.host == 'jobprogress.com'  ||  window.location.host == 'www.jobprogress.com') {
				return live();
			}

			// Staging Server
			if( window.location.host == 'jobprog.net' ) {
				return staging();
			}

			// QA Server
			if( window.location.host == 'qa.jobprog.net'  ) {
				return qa();
			}

			return local();
		},
		loadEnv: function() {
			if( window.location.host == 'jobprogress.com'  ||  window.location.host == 'www.jobprogress.com') {
				return liveEnv();
			}

 			// Staging Server
			if( window.location.host == 'jobprog.net' ) {
				return stageEnv();
			}

 			return defaultEnv();
		}
	};

	String.prototype.replaceAll = function(search, replacement) {
	    var target = this;
	    return target.replace(new RegExp(search, 'g'), replacement);
	};

	window.JPAPP.loadEnv();
})();