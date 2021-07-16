<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfDocumentEntity extends AppEntity
{
    protected $af_id;
    protected $folder_id;
    protected $name;
    protected $content_type;
    protected $type;
    protected $is_public;
    protected $body_length;
    protected $body_length_compressed;
    protected $description;
    protected $keywords;
    protected $is_internal_use_only;
    protected $author_id;
    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                            = ine($data, 'id') ? $data['id'] : null;
        $this->folder_id                        = ine($data, 'folderid') ? $data['folderid'] : null;
        $this->name                             = ine($data, 'name') ? $data['name'] : null;
        $this->content_type                     = ine($data, 'contenttype') ? $data['contenttype'] : null;
        $this->type                             = ine($data, 'type') ? $data['type'] : null;
        $this->is_public                        = (bool) ine($data, 'ispublic') ? $data['ispublic'] : null;
        $this->body_length                      = ine($data, 'bodylength') ? $data['bodylength'] : null;
        $this->body_length_compressed           = ine($data, 'bodylengthcompressed') ? $data['bodylengthcompressed'] : null;
        $this->description                      = ine($data, 'description') ? $data['description'] : null;
        $this->keywords                         = ine($data, 'keywords') ? $data['keywords'] : null;
        $this->is_internal_use_only             = (bool) ine($data, 'isinternaluseonly') ? $data['isinternaluseonly'] : null;
        $this->author_id                        = ine($data, 'authorid') ? $data['authorid'] : null;
        $this->options                          = json_encode($data);
        $this->created_by                       = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by                       = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

    }

    public function get()
    {
        return [
            'company_id'                        => $this->companyId,
            'group_id'                          => $this->groupId,
            'af_id'                             => $this->af_id,
            'folder_id'                         => $this->folder_id,
            'name'                              => $this->name,
            'content_type'                      => $this->content_type,
            'type'                              => $this->type,
            'is_public'                         => (bool) $this->is_public,
            'body_length'                       => $this->body_length,
            'body_length_compressed'            => $this->body_length_compressed,
            'description'                       => $this->description,
            'keywords'                          => $this->keywords,
            'is_internal_use_only'              => (bool) $this->is_internal_use_only,
            'author_id'                         => $this->author_id,
            'options'                           => $this->options,
            'created_by'                        => $this->created_by,
            'updated_by'                        => $this->updated_by,
            'csv_filename'                      => $this->csv_filename,
        ];
    }
}
