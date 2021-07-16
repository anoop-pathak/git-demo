<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use FlySystem;

class FinancialProductImage extends BaseModel
{
    use SoftDeletes;

	protected $fillable = ['company_id', 'product_id', 'name', 'size', 'thumb_exists', 'path', 'mime_type'];

    protected $dates = ['deleted_at'];

    protected $imageRules = [
		'product_id' 		=> 'required|integer',
		'images' => 'required|array|max:5'
	];

    protected function getSaveImageRules()
	{
		$validFiles = implode(',', config('resources.image_types'));
		$rules = [
			'product_id'	 => 'required|integer',
			'images' => 'required|mime_types:'.$validFiles
		];

        return $rules;
	}

    public function getURlAttribute()
	{
		return FlySystem::publicUrl(config('jp.BASE_PATH').$this->path);
	}

    public function getThumbURlAttribute()
	{
		if(!$this->thumb_exists) {
			return config('app.url').'generating-thumb.jpg';
        }

	    // add thumb suffix in filename for thumb name
		return preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $this->original_file_path);
	}

    public function getOriginalFilePathAttribute()
	{
		return FlySystem::publicUrl(config('jp.BASE_PATH').$this->path);
	}
}