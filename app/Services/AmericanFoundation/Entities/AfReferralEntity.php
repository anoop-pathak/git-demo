<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfReferralEntity extends AppEntity
{
    protected $name;
    protected $af_id;
    protected $options = [];

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id   = ine($data, 'id') ? $data['id'] : null;
        $this->name    = ine($data, 'name') ? $data['name'] : null;
        $this->options = json_encode($data);
    }

    public function get()
    {
        return [
            'company_id'   => $this->companyId,
            'group_id'     => $this->groupId,
            'af_id'        => $this->af_id,
            'name'         => $this->name,
            'options'      => $this->options,
            'csv_filename' => $this->csv_filename,
        ];
    }
}
