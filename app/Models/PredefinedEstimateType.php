<?php
namespace App\Models;

class PredefinedEstimateType extends BaseModel
{
    const LAYERS = 'layers';
	const VENTILATION = 'ventilation';
	const STRUCTURE = 'structure';
	const COMPLEXITY = 'complexity';

    protected $fillable = ['name', 'type', 'icon'];

    public function estimateLayers(){
        return  $this->hasOne(EstimateTypeLayer::class, 'type_id', 'id')
            ->where('estimate_type_layers.company_id', getScopeId());
    }
}