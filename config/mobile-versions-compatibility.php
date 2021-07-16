<?php
// Set current release version here. API will be break for lower versions..
return [
    '2.3.1' => [
        'FinancialCategoriesController@index',
    ],

    // starting from top.. ^

    '2.1.3' => [
        'FinancialCategoriesController@index',
        'FinancialProductsController@index',
        'FinancialMacrosController@index',
        'FinancialMacrosController@getMultipleMacros',
        'WorksheetController@store',
        'WorksheetController@pdfPreview',
        'ChangeOrdersController@updateChangeOrder',
    ],
    '2.1.4' => [
        'MaterialListsController@index',
        'JobSchedulesController@index',
    ],
    '2.1.5' => [
        'WorksheetController@store',
        'WorksheetController@pdfPreview',
        'WorksheetController@getWorksheet',
    ],
    '2.1.6' => [
        'MaterialListsController@index',
    ],
    '2.2.0' => [
        'JobSchedulesController@updateSchedule',
        'JobSchedulesController@deleteSchedule',
        'break@break',
    ],
    '2.2.9' => [
        'WorksheetController@store',
        'WorksheetController@getWorksheet',
    ],
    '2.3.5' => [
        'FinancialCategoriesController@index',
        'WorksheetController@store',
        'WorksheetController@getWorksheet',
        'AppointmentsController@show',
        'AppointmentsController@update',
    ],
    '2.3.6' => [
        'JobSchedulesController@show',
        'JobSchedulesController@makeSchedule',
        'JobSchedulesController@index',
        'JobSchedulesController@updateSchedule',
    ],
    '2.4.6' => [
        'FinancialCategoriesController@index',
        'AppointmentsController@update',
        'JobSchedulesController@updateSchedule',
        'JobsController@store',
        'JobsController@update',
        'JobCreditsController@index',
        'FinancialDetailsController@jobAmount',
    ],

    //2019-05-03
	'2.4.5' => [
		'FinancialCategoriesController@index',
		'EagleViewController@get_products',
		'AppointmentsController@addResult',
		'JobsController@store',
		'JobsController@update',
		'v2\JobInvoicesController@createInvoice',
		'v2\JobInvoicesController@update',
		'ChangeOrdersController@saveChangeOrder',
		'ChangeOrdersController@getByInvoiceId',
		'JobInvoicesController@show',
		'ChangeOrdersController@getChangeOrder',
	],

    //2019-08-24
	'2.4.7' => [
		'IncompleteSignupsController@store',
		'FinancialCategoriesController@index',
    ],

    //2019-09-30
	'2.5.0' => [
		'TasksController@store',
		'TasksController@update',
		'AppointmentsController@store',
		'AppointmentsController@update',
		'MessagesController@send_message',
		'JobsController@store',
		'JobsController@update',
		'JobSchedulesController@makeSchedule',
		'JobSchedulesController@updateSchedule',
		'JobsController@assignUsers',
    ],

    //2019-11-09
	'2.5.2' => [
		'WorksheetController@store',
		'AppointmentsController@index',
    ],

    // 2019-12-03
	'2.5.3' => [
		'FinancialCategoriesController@index',
		'JobsController@update_stage'
    ],

    // 2020-01-06
	'2.5.6' => [
		'FinancialCategoriesController@index',
		'SRSController@submitOrder'
    ],

    //2020-02-04
	'2.5.7' => [
		'FinancialCategoriesController@index',
    ],

    //2020-06-13
	'2.6.5' => [
		'JobProgress\Tasks\Controller\TasksController@update',
    ],

    '2.6.6' => [
		'TemplatesController@index',
    ],

    '2.6.9' => [
		'CompanyContactsController@index',
		'CompanyContactsController@store',
		'JobsController@store',
		'JobsController@update',
		'FinancialCategoriesController@index',
	],


	'2.6.10' => [
		'MeasurementController@store',
		'MeasurementController@show',
		'MeasurementController@update',
		'MeasurementFormulaController@getAttributeList',
	],

];
