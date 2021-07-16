<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfJobEntity extends AppEntity
{
    protected $af_id;
    protected $owner_id;
    protected $name;
    protected $job_number;
    protected $comments;
    protected $job_type;
    protected $project_id;
    protected $project_manager_id;
    protected $project_number;
    protected $af_customer_af_id;
    protected $status;
    protected $total_cost;
    protected $receipts_adjustment_total;
    protected $total_sale;
    protected $summary_total_with_tax;
    protected $total_with_tax;
    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                        = ine($data, 'id') ? $data['id'] : null;
        $this->owner_id                     = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->name                         = ine($data, 'name') ? $data['name'] : null;
        $this->job_number                   = ine($data, 'job_number_c') ? $data['job_number_c'] : null;
        $this->comments                     = ine($data, 'i360_comments_c') ? $data['i360_comments_c'] : null;
        $this->job_type                     = ine($data, 'i360_job_type_c') ? $data['i360_job_type_c'] : null;
        $this->project_id                   = ine($data, 'i360_project_id_text_c') ? $data['i360_project_id_text_c'] : null;
        $this->project_manager_id           = ine($data, 'i360_project_manager_c') ? $data['i360_project_manager_c'] : null;
        $this->project_number               = ine($data, 'i360_project_number_c') ? $data['i360_project_number_c'] : null;
        $this->af_customer_af_id            = ine($data, 'i360_prospect_c') ? $data['i360_prospect_c'] : null;
        $this->status                       = ine($data, 'i360_status_c') ? $data['i360_status_c'] : null;
        $this->total_cost                   = ine($data, 'i360_project_costs_total_c') ? $data['i360_project_costs_total_c'] : null;
        $this->receipts_adjustment_total    = ine($data, 'supportworks_receipts_adjustment_total_c') ? $data['supportworks_receipts_adjustment_total_c'] : null;
        $this->total_sale                   = ine($data, 'supportworks_total_sale_items_c') ? $data['supportworks_total_sale_items_c'] : null;
        $this->summary_total_with_tax       = ine($data, 'supportworks_summary_total_with_tax_c') ? $data['supportworks_summary_total_with_tax_c'] : null;
        $this->total_with_tax               = ine($data, 'supportworks_total_with_tax_c') ? $data['supportworks_total_with_tax_c'] : null;
        $this->created_by                   = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by                   = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

        $this->options          = json_encode($data);
    }

    public function get()
    {
        return [
            'company_id'                => $this->companyId,
            'group_id'                  => $this->groupId,
            'af_id'                     => $this->af_id,
            'owner_id'                  => $this->owner_id,
            'name'                      => $this->name,
            'job_number'                => $this->job_number,
            'comments'                  => $this->comments,
            'job_type'                  => $this->job_type,
            'project_id'                => $this->project_id,
            'project_manager_id'        => $this->project_manager_id,
            'project_number'            => $this->project_number,
            'af_customer_af_id'         => $this->af_customer_af_id,
            'status'                    => $this->status,
            'total_cost'                => $this->total_cost,
            'receipts_adjustment_total' => $this->receipts_adjustment_total,
            'total_sale'                => $this->total_sale,
            'summary_total_with_tax'    => $this->summary_total_with_tax,
            'total_with_tax'            => $this->total_with_tax,
            'created_by'                => $this->created_by,
            'updated_by'                => $this->updated_by,
            'csv_filename'              => $this->csv_filename,
            'options'                   => $this->options,
        ];
    }
}
