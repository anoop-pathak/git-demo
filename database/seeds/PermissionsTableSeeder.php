<?php

use Illuminate\Database\Seeder;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\App;

class PermissionsTableSeeder extends Seeder {
    public function run()
    {
        // seed permissions
        $permissions = config('permissions-map');
        foreach ($permissions as $permissionArr) {
            $permissionArr = array_unique($permissionArr);
            foreach ($permissionArr as $permission) {
                Permission::firstOrCreate([
                    'name'	=> $permission,
                    'display_name'	=> ucwords(str_replace('_', ' ', $permission)),
                ]);
            }
        }

        $additionalPermissoins = [
			'manage_company_files',
			'view_company_files',
			'manage_resource_viewer',
			'view_resource_viewer',
			'approve_job_price_request',
			'manage_job_directory',
			'mark_task_unlock',
			'view_unit_cost',
			'user_mobile_tracking',
			'message_to_user_groups',
			'worksheet_settings_within_job'
		];

		foreach ($additionalPermissoins as $additionalPermissoin) {
			Permission::firstOrCreate([
				'name'	=> $additionalPermissoin,
				'display_name'	=> ucwords(str_replace('_', ' ', $additionalPermissoin)),
			]);
		}

        // seed roles
        $rolesPermissions = [
            'super-admin' => [
                'super_admin_only',
                'manage_workflow',
                'view_workflow_stages',
                'view_company_profile',
                'manage_company',
                'manage_company_trades',
                'manage_company_states',
                'manage_billing_info',
                'manage_users',
                'view_users',
                'user_profile',
                'user_devices_list',
                'manage_settings',
                'manage_referrals',
                'view_referrals',
                'manage_templates',
                'view_templates',
                'manage_job_types',
                'view_job_types',
                'activity_feed',
                'view_invoices',
                'email',
                'manage_incomplete_signup',
                'onboard_checklist_manager',
                'classified_and_product_focus',
                'manage_financial',
				'view_materials',
                'update_job_price',
				'user_mobile_tracking',
            ],
            'basic-admin' => [
                'view_workflow_stages',
                'view_company_profile',
                'manage_company',
                'manage_company_trades',
                'manage_company_states',
                'manage_billing_info',
                'manage_users',
                'view_users',
                'user_profile',
                'manage_customers',
                'delete_customer',
                'view_customers',
                'manage_customer_rep',
                'import_customers',
                'export_customers_pdf',
                'export_customers',
                'manage_jobs',
                'delete_job',
                'view_jobs',
                'export_jobs',
                'add_job_note',
                'manage_appointments',
                'manage_company_contacts',
                'view_company_contacts',
                'manage_tasks',
                'view_templates',
                'view_notifications',
                'view_invoices',
                'manage_referrals',
                'view_referrals',
                'activity_feed',
                'add_activity_feed',
                'job_schedules',
                'manage_estimates',
                // 'view_estimates',
                'user_devices_list',
                'manage_settings',
                // 'estimates_file_upload',
                // 'delete_estimates',
                // 'proposals_file_upload',
                // 'delete_proposals',
                'manage_job_documents',
                'view_job_documents',
                'email',
                'view_sent_emails',
                'manage_job_types',
                'view_job_types',
                'manage_job_workflow',
                'account_unsubscribe',
                'delete_followup_note',
                'connect_social_network',
                'manage_social_network',
                'view_sale_performance_report',
                'view_company_performance_report',
                'view_market_source_report',
                'view_owd_to_company_report',
                'view_proposal_report',
                'view_commission_report',
                'view_master_list_report',
                'view_moved_to_stage_report',
                'view_sales_tax_report',
                'skymeasure',
                'eagleview',
                'share_customer_web_page',
                'hover',
                'manage_job_followup',
                'view_job_followup',
                'manage_full_job_workflow',
				// 'manage_job_workcrew',
				'manage_job_schedule',
                'update_job_price',
				'view_progress_board',
				'manage_progress_board',
				'manage_company_files',
				'view_company_files',
				'manage_resource_viewer',
				'view_resource_viewer',
				'approve_job_price_request',
				'change_proposal_status',
				'view_proposals',
				'manage_job_directory',
				'mark_task_unlock',
				'view_unit_cost',
				'user_mobile_tracking',
				'message_to_user_groups',
				'worksheet_settings_within_job'
            ],
            'basic-standard' => [
                'view_workflow_stages',
                'view_company_profile',
                'view_users',
                'user_profile',
                'manage_customers',
                'view_customers',
                'manage_customer_rep',
                'manage_jobs',
                'view_jobs',
                'export_jobs',
                'add_job_note',
                'manage_appointments',
                'manage_company_contacts',
                'view_company_contacts',
                'manage_tasks',
                'view_templates',
                'view_notifications',
                'view_referrals',
                'activity_feed',
                'add_activity_feed',
                'job_schedules',
                'manage_estimates',
                // 'view_estimates',
                'user_devices_list',
                'manage_settings',
                // 'estimates_file_upload',
                // 'delete_estimates',
                // 'proposals_file_upload',
                // 'delete_proposals',
                'manage_job_documents',
                'view_job_documents',
                'email',
                'view_sent_emails',
                'view_job_types',
                'manage_job_workflow',
                'import_customers',
                'export_customers_pdf',
                'view_sale_performance_report',
                'view_company_performance_report',
                'view_market_source_report',
                'view_owd_to_company_report',
                'view_proposal_report',
                'view_commission_report',
                'view_master_list_report',
                'view_moved_to_stage_report',
                // 'view_sales_tax_report',
                'skymeasure',
                'eagleview',
                'hover',
                'manage_job_followup',
                'view_job_followup',
                'manage_full_job_workflow',
				// 'manage_job_workcrew',
				'manage_job_schedule',
                'view_company_files',
				'view_resource_viewer',
				'view_progress_board',
				'manage_progress_board',
				'change_proposal_status',
				'view_proposals',
				'mark_task_unlock',
				'view_unit_cost',
				'user_mobile_tracking',
				'message_to_user_groups',
            ],
            'plus-admin' => [
                'manage_workflow',
                'view_workflow_stages',
                'view_company_profile',
                'manage_company',
                'manage_company_trades',
                'manage_company_states',
                'manage_billing_info',
                'manage_users',
                'view_users',
                'user_profile',
                'manage_customers',
                'delete_customer',
                'view_customers',
                'manage_customer_rep',
                'import_customers',
                'export_customers_pdf',
                'export_customers',
                'manage_jobs',
                'delete_job',
                'view_jobs',
                'export_jobs',
                'add_job_note',
                'manage_appointments',
                'manage_company_contacts',
                'view_company_contacts',
                'manage_messages',
                'manage_tasks',
                'manage_templates',
                'view_templates',
                'view_notifications',
                'view_invoices',
                'manage_referrals',
                'view_referrals',
                'manage_settings',
                'activity_feed',
                'add_activity_feed',
                'job_schedules',
                'manage_estimates',
                // 'view_estimates',
                'manage_proposals',
                'manage_financial',
                'view_financial',
                'view_profit_loss_sheets',
                'view_selling_price_sheets',
                'user_devices_list',
                // 'estimates_file_upload',
                // 'delete_estimates',
                // 'proposals_file_upload',
                // 'delete_proposals',
                'manage_job_documents',
                'view_job_documents',
                'manage_stage_resources',
                'view_stage_resources',
                'email',
                'view_sent_emails',
                'manage_job_types',
                'view_job_types',
                'manage_job_workflow',
                'account_unsubscribe',
                'delete_followup_note',
                'connect_social_network',
                'manage_social_network',
                'view_sale_performance_report',
                'view_company_performance_report',
                'view_market_source_report',
                'view_owd_to_company_report',
                'view_proposal_report',
                'view_commission_report',
                'view_master_list_report',
                'view_moved_to_stage_report',
                'view_sales_tax_report',
                'skymeasure',
                'eagleview',
                'share_customer_web_page',
                'classified_and_product_focus',
                'hover',
                'manage_job_followup',
                'view_job_followup',
                'view_materials',
                'manage_full_job_workflow',
				// 'manage_job_workcrew',
				'manage_job_schedule',
                'update_job_price',
				'view_progress_board',
				'manage_progress_board',
				'manage_company_files',
				'view_company_files',
				'manage_resource_viewer',
				'view_resource_viewer',
				'approve_job_price_request',
				'change_proposal_status',
				'view_proposals',
				'manage_job_directory',
				'mark_task_unlock',
				'view_unit_cost',
				'user_mobile_tracking',
				'message_to_user_groups',
				'worksheet_settings_within_job'
            ],
            'plus-standard' => [
                'view_workflow_stages',
                'view_company_profile',
                'view_users',
                'user_profile',
                'manage_customers',
                'view_customers',
                'manage_customer_rep',
                'manage_jobs',
                'view_jobs',
                'export_jobs',
                'add_job_note',
                'manage_appointments',
                'manage_company_contacts',
                'view_company_contacts',
                'manage_messages',
                'manage_tasks',
                'view_templates',
                'view_notifications',
                'view_referrals',
                'manage_settings',
                'activity_feed',
                'add_activity_feed',
                'job_schedules',
                'manage_estimates',
                // 'view_estimates',
                'manage_proposals',
                'user_devices_list',
                // 'estimates_file_upload',
                // 'delete_estimates',
                // 'proposals_file_upload',
                // 'delete_proposals',
                'manage_job_documents',
                'view_job_documents',
                'view_stage_resources',
                'email',
                'view_sent_emails',
                'view_job_types',
                'manage_job_workflow',
                'import_customers',
                'export_customers_pdf',
                'view_financial',
                'view_sale_performance_report',
                'view_company_performance_report',
                'view_market_source_report',
                'view_owd_to_company_report',
                'view_proposal_report',
                'view_commission_report',
                'view_master_list_report',
                'view_moved_to_stage_report',
                // 'view_sales_tax_report',
                'skymeasure',
                'eagleview',
                'hover',
                'manage_job_followup',
                'view_job_followup',
                'manage_full_job_workflow',
				// 'manage_job_workcrew',
				'manage_job_schedule',
                'view_company_files',
				'view_resource_viewer',
				'view_progress_board',
				'manage_progress_board',
				'change_proposal_status',
				'view_proposals',
				'manage_job_directory',
				'mark_task_unlock',
				'view_unit_cost',
				'user_mobile_tracking',
				'message_to_user_groups',
            ],
            'sub-contractor' => [
                'subcontractor_page_apis',
                'user_mobile_tracking',
            ],
            'sub-contractor-prime' => [
                'view_workflow_stages',
                'view_company_profile',
                'view_users',
                'user_profile',
                'view_customers',
                'view_jobs',
                'manage_appointments',
                'manage_messages',
                'manage_tasks',
                'view_notifications',
                'manage_settings',
                'activity_feed',
                'job_schedules',
                'manage_estimates',
                'manage_proposals',
                'manage_job_documents',
                'view_job_documents',
                'view_stage_resources',
                'email',
                'view_sent_emails',
                'view_job_types',
                'export_customers_pdf',
                'skymeasure',
                'eagleview',
                'hover',
                'add_job_note',
                'export_jobs',
                'add_activity_feed',
                'view_materials',
                'manage_full_job_workflow',
				// 'manage_job_workcrew',
				'manage_job_schedule',
                'view_unit_cost',
				'user_mobile_tracking',
				'message_to_user_groups',
				'worksheet_settings_within_job'
            ],
            'open-api' => [
				'manage_customers'
			]

        ];

        foreach ($rolesPermissions as $role => $permissions) {
            $role = Role::firstOrCreate([
                'name'  => $role
            ]);
            $permissionsList = Permission::whereIn('name',$permissions)->pluck('id')->toArray();
            $role->perms()->sync($permissionsList);
        }
    }
    private function assignRolesToUsers() {
        $userRepo = App::make('App\Repositories\UserRepository');
        $users = User::get();
        foreach ($users as $user) {
            $userRepo->assignRole($user);
        }
    }
}