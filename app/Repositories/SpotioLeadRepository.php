<?php

namespace App\Repositories;

use App\Models\SpotioLead;
use App\Services\Contexts\Context;

class SpotioLeadRepository extends ScopedRepository
{
    /**
     * Model Instance
     * @var App\Models\SpotioLead
     */
    protected $model;

    /**
     * Scope Instance
     * @var App\Services\Contexts\Context
     */
    protected $scope;

    /**
     * Class Constructor
     */
    public function __construct(SpotioLead $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Get Lead
     * @param  $leadId
     * @return Instance
     */
    public function getLead($leadId)
    {
        return $this->make()->where('lead_id', $leadId)->first();
    }

    /**
     * Create Lead
     * @param  array  $metaData
     * @return response
     */
    public function createLead($entity)
    {
    	$payload = $this->setDBPayload($entity);

        $obj = $this->model->create($payload);

        return $obj;
    }

    /**
     * update Lead
     * @return response
     */
    public function updateLead($lead, $entity)
    {
        $payload = $this->setDBPayload($entity);

        $lead->update($payload);

        return $lead;
    }

    public function updateDocumets($lead, $entity)
    {
        $payload['documents_list'] = json_encode($entity->getDocumentsList());

        $lead->update($payload);

        return $lead;
    }
    
    public function updateLogs($message, $fileName, $lineNumber, $spotio, $type, $e = null)
    {
        $lead = $this->model->where('id', $spotio->id)->where('company_id', $this->scope->id())->first();

        if($lead) {
            if(!$lead->log_messages) {
                $log[] = [
                    'type' => $type,
                    'file_name' => $fileName,
                    'line_number' => $lineNumber,
                    'message' => $message,
                ];

                $lead->log_messages = json_encode($log);
            } else {
                $logs = json_decode($lead->log_messages, true);
                $newLog[] = [
                    'type' => $type,
                    'file_name' => $fileName,
                    'line_number' => $lineNumber,
                    'message' => $message,
                ];
                $newLogs = array_merge($logs, $newLog);
                $lead->log_messages = json_encode($newLogs);
            }

            $lead->save();
        }

    }

    /**
     * Set DB Payload for Saving in Table
     * @param $entity
     */
    public function setDBPayload($entity)
    {
        $payload = [
            'company_id'                            => $this->scope->id(),
            'lead_id'                               => $entity->getLeadId(),
            // 'assigned_user_name'                    => $entity->getAssignedUserName(),
            // 'updated_at_external_system_user_id'    => $entity->getUpdatedAtExternalSystemUserId(),
            // 'assigned_external_system_user_id'      => $entity->getAssignedExternalSystemUserId(),
            // 'address_unit'                          => $entity->getAddressUnit(),
            'value'                                 => $entity->getValue(),
            // 'created_at_utc'                        => $entity->getCreatedAtUtc(),
            // 'created_at_local'                      => $entity->getCreatedAtLocal(),
            // 'updated_at_utc'                        => $entity->getUpdatedAtUtc(),
            // 'updated_at_local'                      => $entity->getUpdatedAtLocal(),
            'lat'                                   => $entity->getLatitude(),
            'long'                                  => $entity->getLongitude(),
            'address'                               => $entity->getAddress(),
            'city'                                  => $entity->getCity(),
            'house_number'                          => $entity->getHouseNumber(),
            'street'                                => $entity->getStreet(),
            'zip_code'                              => $entity->getZipCode(),
            'state'                                 => $entity->getState(),
            'country'                               => $entity->getCountry(),
            // 'stage_name'                            => $entity->getStageName(),
            // 'assigned_user_email'                   => $entity->getAssignedUserEmail(),
            // 'assigned_user_phone'                   => $entity->getAssignedUserPhone(),
            // 'updated_at_username'                   => $entity->getUpdatedAtUsername(),
            // 'updated_at_user_email'                 => $entity->getUpdatedAtUserEmail(),
            'company'                               => $entity->getCompany(),
            // 'documents'                             => $entity->getDocuments(),
            'documents_list'                        => json_encode($entity->getDocumentsList()),
            // 'last_visit_result'                     => $entity->getLastVisitResult(),
            'contacts'                              => json_encode($entity->getContacts()),
            // 'contact_custom_fields'                 => json_encode($entity->getContactCustomFields()),
            // 'lead_custom_fields'                    => json_encode($entity->getLeadCustomFields()),
        ];

        return $payload;
    }

    public function getLeads($filters)
    {
        $query = $this->make()->sortable();

        if(ine($filters, 'lead_id')) {
            $query->where('lead_id', $filters['lead_id']);
        }

        if(!ine($filters, 'sort_by')) {
            $query->orderBy('created_at', 'desc');
        }

        if(ine($filters, 'sort_by') && ine($filters, 'order')) {
            $query->orderBy($filters['sort_by'], $filters['order']);
        }

        return $query;
    }
}
