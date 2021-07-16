<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfLeadSourceEntity extends AppEntity
{
    protected $af_id;
    protected $owner_id;
    protected $name;
    protected $comments;
    protected $components;
    protected $prospect_id;
    protected $prospect;
    protected $marketing_source_id;
    protected $prospect_email;
    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                = ine($data, 'id') ? $data['id'] : null;
        $this->owner_id             = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->name                 = ine($data, 'name') ? $data['name'] : null;
        $this->comments             = ine($data, 'i360_comments_c') ? $data['i360_comments_c'] : null;
        $this->components           = ine($data, 'i360_components_c') ? $data['i360_components_c'] : null;
        $this->prospect_id          = ine($data, 'i360_prospect_id_c') ? $data['i360_prospect_id_c'] : null;
        $this->prospect             = ine($data, 'i360_prospect_c') ? $data['i360_prospect_c'] : null;
        $this->marketing_source_id  = ine($data, 'i360_source_c') ? $data['i360_source_c'] : null;
        $this->prospect_email       = ine($data, 'supportworks_prospect_email_ws_c') ? $data['supportworks_prospect_email_ws_c'] : null;
        $this->created_by           = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by           = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;
        $this->options              = json_encode($data);
    }

    public function get()
    {
        return [
            'company_id'            => $this->companyId,
            'group_id'              => $this->groupId,
            'af_id'                 => $this->af_id,
            'owner_id'              => $this->owner_id,
            'name'                  => $this->name,
            'comments'              => $this->comments,
            'components'            => $this->components,
            'prospect_id'           => $this->prospect_id,
            'prospect'              => $this->prospect,
            'marketing_source_id'   => $this->marketing_source_id,
            'prospect_email'        => $this->prospect_email ? trim($this->prospect_email) : null,
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'options'               => $this->options,
            'csv_filename'          => $this->csv_filename,
        ];
    }
}
