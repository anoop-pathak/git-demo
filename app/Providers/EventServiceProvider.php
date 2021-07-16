<?php

/**
 * @TODO: Need to fix the namespaces of handlers.
 */

namespace App\Providers;

use App\Handlers\Events\ActivityLogs\JobRepChangedEventHandler;
use App\Handlers\Events\NotificationEventHandlers\JobRepChangedNotificationEventHandler;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'JobProgress.Jobs.Events.JobRepAssigned' => [
            JobRepChangedNotificationEventHandler::class,
            JobRepChangedEventHandler::class
        ],
        'JobProgress.Jobs.Events.JobPriceRequestSubmitted' => [
            \App\Handlers\Events\NotificationEventHandlers\JobPriceRequestNotificationEventHandler::class
        ],
        'JobProgress.Jobs.Events.JobEstimatorAssigned' => [
            \App\Handlers\Events\NotificationEventHandlers\JobEstimatorChangedNotificationEventHandler::class,
            \App\Handlers\Events\ActivityLogs\JobEstimatorChangedEventHandler::class
        ],
        'JobProgress.Customers.Events.CustomerRepAssigned' => [
            \App\Handlers\Events\NotificationEventHandlers\CustomerRepChangedNotificationEventHandler::class,
            \App\Handlers\Events\ActivityLogs\CustomerRepChangedEventHandler::class
        ],
        'JobProgress.Messages.Events.NewMessageEvent' => [\App\Handlers\Events\NotificationEventHandlers\NewMessageNotificationEventHandler::class],
        'JobProgress.Appointments.Events.AppointmentCreated' => [\App\Handlers\Events\NotificationEventHandlers\NewAppointmentNotificationEventHandler::class],
        'JobProgress.Appointments.Events.AppointmentUpdated' => [\App\Handlers\Events\NotificationEventHandlers\NewAppointmentNotificationEventHandler::class],
        'JobProgress.Announcements.Events.NewAnnouncement' => [\App\Handlers\Events\NotificationEventHandlers\AnnouncementNotificationEventHandler::class],
        'JobProgress.GoogleCalender.Events.GoogleTokenExpired' => [\App\Handlers\Events\NotificationEventHandlers\GoogleTokenExpiredNotificationEventHandler::class],
        'JobProgress.Jobs.Events.JobStageChanged' => [
            \App\Handlers\Events\ActivityLogs\JobStageChangedEventHandler::class,
            \App\Handlers\Events\DripCampaigns\DripCampaignVerifyJobStageAndChangeStatusEventHandler::class
        ],
        'JobProgress.Jobs.Events.CloseDripCampaignOfLastStage' => [\App\Handlers\Events\DripCampaigns\DripCampaignVerifyJobStageAndChangeStatusEventHandler::class],
        'JobProgress.Jobs.Events.JobNoteAdded' => [\App\Handlers\Events\ActivityLogs\JobNoteAddedEventHandler::class],
        'JobProgress.Jobs.Events.JobNoteUpdated' => [\App\Handlers\Events\ActivityLogs\JobNoteUpdatedEventHandler::class],
        'JobProgress.Workflow.Steps.Estimation.Events.EstimationCreated' => [\App\Handlers\Events\ActivityLogs\EstimationCreatedEventHandler::class],
        'JobProgress.Workflow.Steps.Estimation.Events.JobEstimationDeleted' => [\App\Handlers\Events\ActivityLogs\JobEstimationDeletedEventHandler::class],
        'JobProgress.Workflow.Steps.Proposal.Events.ProposalCreated' => [\App\Handlers\Events\ActivityLogs\ProposalCreatedEventHandler::class],
        'JobProgress.Workflow.Steps.Proposal.Events.JobProposalDeleted' => [\App\Handlers\Events\ActivityLogs\JobProposalDeletedEventHandler::class],
        'JobProgress.Jobs.Events.DocumentUploaded' => [\App\Handlers\Events\ActivityLogs\DocumentUploadedEventHandler::class],
        'JobProgress.Jobs.Events.JobDocumentDeleted' => [\App\Handlers\Events\ActivityLogs\JobDocumentDeletedEventHandler::class],
        'JobProgress.Jobs.Events.JobScheduled' => [\App\Handlers\Events\NotificationEventHandlers\JobScheduleNotificationEventHandler::class],
        'JobProgress.Jobs.Events.JobScheduleUpdated' => [\App\Handlers\Events\NotificationEventHandlers\JobScheduleUpdatedNotificationEventHandler::class],
        'JobProgress.Users.Events.UserActivated' => [\App\Handlers\Events\ActivityLogs\UserActivationDeactivationEventHandler::class],
        'JobProgress.Users.Events.UserDeactivated' => [\App\Handlers\Events\ActivityLogs\UserActivationDeactivationEventHandler::class],
        'JobProgress.Subscriptions.Events.SubscriberSuspended' => [\App\Handlers\Events\ActivityLogs\SubscriberSuspendedEventHandler::class],
        'JobProgress.Subscriptions.Events.SubscriberManuallySuspended' => [\App\Handlers\Events\ActivityLogs\SubscriberSuspendedEventHandler::class],
        'JobProgress.Subscriptions.Events.SubscriberTerminated' => [\App\Handlers\Events\ActivityLogs\SubscriberTerminatedEventHandler::class],
        'JobProgress.Subscriptions.Events.SubscriberUnsubscribed' => [\App\Handlers\Events\ActivityLogs\SubscriberUnsubscribedEventHandler::class],
        'JobProgress.Subscriptions.Events.SubscriberReactivated' => [\App\Handlers\Events\ActivityLogs\SubscriberReactivatedEventHandler::class],
        'JobProgress.Customers.Events.TempImportCustomerDeleted' => [\App\Handlers\Events\ActivityLogs\TempImportCustomerDeletedEventHandler::class],
        'JobProgress.Customers.Events.LostJobEventHandler' => [\App\Handlers\Events\ActivityLogs\LostJobEventHandler::class],
        'JobProgress.MaterialLists.Events.MaterialListCreated' => [\App\Handlers\Events\ActivityLogs\MaterialListCreatedEventHandler::class],
        'JobProgress.WorkOrders.Events.WorkOrderCreated' => [\App\Handlers\Events\ActivityLogs\WorkOrderCreatedEventHandler::class],
        'JobProgress.Resources.Events.ResourcesMoved' => [\App\Handlers\Events\ActivityLogs\ResourceMovedEventHandler::class],
        'JobProgress.DripCampaigns.Events.DripCampaignCreated' => [\App\Handlers\Events\ActivityLogs\DripCampaignCreatedEventHandler::class],
        'JobProgress.DripCampaigns.Events.DripCampaignCanceled' => [\App\Handlers\Events\ActivityLogs\DripCampaignCanceledEventHandler::class],
        'JobProgress.DripCampaigns.Events.SendDripCampaignSchedulers' => [\App\Handlers\Events\ActivityLogs\SendDripCampaignSchedulersEventHandler::class],
        'JobProgress.DripCampaigns.Events.DripCampaignClosed' => [\App\Handlers\Events\ActivityLogs\DripCampaignClosedEventHandler::class],
        // 'illuminate.log' => [\App\Handlers\Events\LogsEventHandler::class],
        'JobProgress.QuickBookDesktop.Events.QBDesktopWorksheetFailed' => [\App\Handlers\Events\NotificationEventHandlers\QBDWorksheetFailedNotificationEventHandler::class],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Handlers\Events\SubscriberEventHandler::class,
        \App\Handlers\Events\AppointmentEventHandler::class,
        \App\Handlers\Events\UserEventHandler::class,
        \App\Handlers\Events\JobEventHandler::class,
        \App\Handlers\Events\SubscriptionEventHandler::class,
        \App\Handlers\Events\ProposalEventHandler::class,
        \App\Handlers\Events\TaskEventHandler::class,
        \App\Handlers\Events\CustomerEventHandler::class,
        \App\Handlers\Events\MessageEventHandler::class,
        // \App\Models\Observers\JobModelEvent::class,
        // \App\Models\Observers\CustomerModelEvent::class,
        // \App\Models\Observers\AccountManagerModelEvent::class,
        // \App\Models\Observers\ProposalModelEvent::class,
        // \App\Models\Observers\EstimationModelEvent::class,
        // \App\Models\Observers\SubscriptionModelEvent::class,
        // \App\Models\Observers\UserModelEvent::class,
        // \App\Models\Observers\SettingModelEvent::class,
        // \App\Models\Observers\MaterialListModelEvent::class,
        // \App\Models\Observers\WorkCrewNoteModelEvent::class,
        // \App\Models\Observers\JobInvoiceModelEvent::class,
        // \App\Models\Observers\TimeLogModelEvent::class,
        \App\Handlers\Events\FolderEventHandler::class,
        \App\Handlers\Events\VendorBillEventHandler::class,
        \App\Handlers\Events\VendorEventHandler::class,
        \App\Handlers\Events\FinancialAccountEventHandler::class,
        \App\Handlers\Events\RefundEventHandler::class,
        \App\Handlers\Events\TwilioEventHandler::class,

        //QBO Event Handler
        \App\Services\QuickBooks\JPSystemEventHandlers\CustomerEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\JobEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\InvoiceEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\PaymentEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\CreditEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\AccountEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\VendorEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\BillEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\AttachmentEventHandler::class,
        \App\Services\QuickBooks\JPSystemEventHandlers\RefundEventHandler::class,

        //QBD Event Handler
        \App\Services\QuickBookDesktop\SystemEventHandlers\CustomerEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\JobEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\AccountEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\VendorEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\BillEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\FinancialCategoryEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\InvoiceEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\CreditEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\PaymentEventHandler::class,
        \App\Services\QuickBookDesktop\SystemEventHandlers\WorkSheetEventHandler::class,

        \App\Handlers\Events\OpenApiWebhooks\JobEventHandler::class,
        \App\Handlers\Events\OpenApiWebhooks\CustomerEventHandler::class,
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        \App\Models\AccountManager::observe(\App\Observers\AccountManagerObserver::class);
        \App\Models\Customer::observe(\App\Observers\CustomerObserver::class);
        \App\Models\Estimation::observe(\App\Observers\EstimationObserver::class);
        \App\Models\FinancialProduct::observe(\App\Observers\FinancialProductObserver::class);
        \App\Models\JobInvoice::observe(\App\Observers\JobInvoiceObserver::class);
        \App\Models\Job::observe(\App\Observers\JobObserver::class);
        \App\Models\JobPriceRequest::observe(\App\Observers\JobPriceRequestObserver::class);
        \App\Models\MaterialList::observe(\App\Observers\MaterialListObserver::class);
        \App\Models\Proposal::observe(\App\Observers\ProposalObserver::class);
        \App\Models\Setting::observe(\App\Observers\SettingObserver::class);
        \App\Models\TimeLog::observe(\App\Observers\TimeLogObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\WorkCrewNote::observe(\App\Observers\WorkCrewNoteObserver::class);
        \App\Models\Worksheet::observe(\App\Observers\WorksheetObserver::class);
        \App\Models\CumulativeInvoiceNote::observe(\App\Observers\CumulativeInvoiceNoteObserver::class);
        \App\Models\Vendor::observe(\App\Observers\VendorObserver::class);
        \App\Models\VendorBill::observe(\App\Observers\VendorBillObserver::class);
        \App\Models\FinancialAccount::observe(\App\Observers\FinancialAccountObserver::class);
        \App\Models\JobRefund::observe(\App\Observers\JobRefundObserver::class);
        \App\Models\DripCampaign::observe(\App\Observers\DripCampaignObserver::class);
        \App\Models\Contact::observe(\App\Observers\ContactObserver::class);
        \App\Models\JobFollowUp::observe(\App\Observers\JobFollowUpObserver::class);
        \App\Models\JobNote::observe(\App\Observers\JobNoteObserver::class);
    }
}
