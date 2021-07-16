<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstimationPage extends Model
{

    protected $fillable = ['template', 'template_cover', 'image', 'thumb', 'estimation_id', 'order'];

    public function estimation()
    {
        return $this->belongsTo(Estimation::class);
    }
}
