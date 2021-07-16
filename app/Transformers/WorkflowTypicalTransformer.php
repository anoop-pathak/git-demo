<?php

namespace App\Transformers;

class WorkflowTypicalTransformer extends Transformer
{

    public function transform($workflow)
    {
        $workflow = json_decode($workflow, true);
        $ret = $stages = [];
        foreach ($workflow['stages'] as $key => $value) {
            $steps = [];
            if (isset($value['steps']) && !empty($value['steps'])) {
                foreach ($value['steps'] as $key => $step) {
                    $steps[] = [
                        'id' => $step['id'],
                        'name' => $step['name'],
                        'code' => $step['code'],
                        'required' => (int)$step['required'],
                        'action_step' => $step['action_step'],
                        'controls' => (!empty($step['options'])) ? json_decode($step['options']) : null,
                        'position' => (int)$step['position'],
                    ];
                }
            }

            $stages[] = [
                'id' => $value['id'],
                'name' => $value['name'],
                'code' => $value['code'],
                'locked' => (int)$value['locked'],
                'position' => (int)$value['position'],
                'color' => $value['color'],
                'resource_id' => $value['resource_id'],
                'options' => $value['options'],
                'steps' => $steps,
                'send_customer_email' => (int)$value['send_customer_email'],
                'send_push_notification' => (int)$value['send_push_notification'],
                'create_tasks' => (int)$value['create_tasks'],
            ];
        }

        $ret[] = [
            'id' => $workflow['id'],
            'company_id' => $workflow['company_id'],
            'title' => $workflow['title'],
            'resource_id' => $workflow['resource_id'],
            'stages' => $stages,
        ];

        return $ret[0];
    }

    public function LibraryStepTransform($list)
    {
        $ret = [];

        foreach ($list as $key => $value) {
            $ret[] = [
                'id' => $value->id,
                'name' => $value->name,
                'code' => $value->code,
                'description' => $value->description,
                'is_custom' => $value->is_custom,
            ];
        }

        return $ret;
    }
}
