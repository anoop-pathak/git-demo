<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfAttachmentEntity extends AppEntity
{
    protected $af_id;
    protected $af_owner_id;
    protected $feed_item_id;
    protected $parent_id;
    protected $account_id;
    protected $name;
    protected $content_type;
    protected $body_length;
    protected $body_length_compressed;
    protected $description;
    protected $is_private;
    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                    = ine($data, 'id') ? $data['id'] : null;
        $this->af_owner_id              = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->feed_item_id             = ine($data, 'feeditemid') ? $data['feeditemid'] : null;
        $this->parent_id                = ine($data, 'parentid') ? $data['parentid'] : null;
        $this->account_id               = ine($data, 'accountid') ? $data['accountid'] : null;
        $this->name                     = ine($data, 'name') ? $data['name'] : null;
        $this->content_type             = ine($data, 'contenttype') ? $data['contenttype'] : null;
        $this->body_length              = ine($data, 'bodylength') ? $data['bodylength'] : null;
        $this->body_length_compressed   = ine($data, 'bodylengthcompressed') ? $data['bodylengthcompressed'] : null;
        $this->description              = ine($data, 'description') ? $data['description'] : null;
        $this->is_private               = ine($data, 'isprivate') ? $data['isprivate'] : null;
        $this->options                  = json_encode($data);
        $this->created_by               = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by               = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

    }

    public function get()
    {
        return [
            'company_id'                => $this->companyId,
            'group_id'                  => $this->groupId,
            'af_id'                     => $this->af_id,
            'af_owner_id'               => $this->af_owner_id,
            'feed_item_id'              => $this->feed_item_id,
            'parent_id'                 => $this->parent_id,
            'account_id'                => $this->account_id,
            'name'                      => $this->name,
            'content_type'              => $this->content_type,
            'body_length'               => $this->body_length,
            'body_length_compressed'    => $this->body_length_compressed,
            'description'               => $this->description,
            'is_private'                => (bool) $this->is_private,
            'options'                   => $this->options,
            'created_by'                => $this->created_by,
            'updated_by'                => $this->updated_by,
            'csv_filename'              => $this->csv_filename,
        ];
    }
}
