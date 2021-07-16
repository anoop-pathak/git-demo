<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfTaskEntity extends AppEntity
{
    protected $af_id;
    protected $af_owner_id;
    protected $who_id;
    protected $what_id;
    protected $task_id;
    protected $subject;
    protected $status;
    protected $priority;
    protected $description;
    protected $options = [];

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id       = ine($data, 'id') ? $data['id'] : null;
        $this->af_owner_id = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->who_id      = ine($data, 'whoid') ? $data['whoid'] : null;
        $this->what_id     = ine($data, 'whatid') ? $data['whatid'] : null;
        $this->subject     = ine($data, 'subject') ? $data['subject'] : null;
        $this->status      = ine($data, 'status') ? $data['status'] : null;
        $this->priority    = ine($data, 'priority') ? $data['priority'] : null;
        $this->description = ine($data, 'description') ? $data['description'] : null;
        $this->options     = json_encode($data);
        $this->created_by  = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by  = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

    }

    public function get()
    {
        return [
            'af_id'        => $this->af_id,
            'company_id'   => $this->companyId,
            'group_id'     => $this->groupId,
            'af_owner_id'  => $this->af_owner_id,
            'who_id'       => $this->who_id,
            'what_id'      => $this->what_id,
            'subject'      => $this->subject,
            'status'       => $this->status,
            'priority'     => $this->priority,
            'description'  => $this->description,
            'options'      => $this->options,
            'csv_filename' => $this->csv_filename,
            'created_by'   => $this->created_by,
            'updated_by'   => $this->updated_by,
        ];
    }
}
