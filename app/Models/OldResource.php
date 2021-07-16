<?php

namespace App\Models;

use Baum\Node;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class OldResource extends Node
{

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'resources';

    protected static $createDirRules = [
        'parent_id' => 'required|integer',
        'name' => 'required|regex:/^(\w+\s\.?)*\w+$/',
    ];

    public static function getCreateDirRules()
    {
        return self::$createDirRules;
    }

    // resource directories..
    const SUBSCRIBER_RESOURCES = 'subscriber_resources';
    const EMAIL_ATTACHMENTS = 'emails_attachments';

    protected static $uploadFileRules = [
        'parent_id' => 'required|integer',
        'image_base_64' => 'nullable|boolean'
    ];

    public static function uploadFileRules()
    {
        $rules = self::$uploadFileRules;
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:' . $validFiles . '|max:' . $maxSize;
        return $rules;
    }

    protected static $resourcesRules = [
        'parent_id' => 'required|integer',
    ];

    public static function getReourcesRules()
    {
        return self::$resourcesRules;
    }

    protected static $renameRules = [
        'id' => 'required|integer',
        'name' => 'required',
    ];

    public static function getRenameRules()
    {
        return self::$renameRules;
    }

    protected static $getFileRules = [
        'id' => 'required|integer',
    ];

    public static function getFileRules()
    {
        return self::$getFileRules;
    }

    public static function getNameAttribute($value)
    {
        return str_replace(' ', '_', $value);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    /**
     * Column name which stores reference to parent's node.
     *
     * @var string
     */
    protected $parentColumn = 'parent_id';

    /**
     * Column name for the left index.
     *
     * @var string
     */
    protected $leftColumn = 'lft';

    /**
     * Column name for the right index.
     *
     * @var string
     */
    protected $rightColumn = 'rgt';

    /**
     * Column name for the depth field.
     *
     * @var string
     */
    protected $depthColumn = 'depth';

    /**
     * Column to perform the default sorting
     *
     * @var string
     */
    protected $orderColumn = 'created_at';

    /**
     * With Baum, all NestedSet-related fields are guarded from mass-assignment
     * by default.
     *
     * @var array
     */
    protected $guarded = ['id', 'parent_id', 'lft', 'rgt', 'depth'];

    public function meta()
    {
        return $this->hasMany(ResourceMeta::class);
    }

    /**
     * limit the scope to directories
     */
    public function scopeDir($query)
    {
        return $query->where('is_dir', true);
    }

    /**
     * limit the scope to files
     */
    public function scopeFile($query)
    {
        return $query->where('is_dir', false);
    }

    public function scopeName($query, $name)
    {
        return $query->where('name', $name);
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCompanyRoot($query, $companyId)
    {
        return $query->where('company_id', $companyId)
            ->where('parent_id', null)->first();
    }

    public function scopeLocked($query)
    {
        return $query->where('locked', true);
    }

    /**
     * **
     * @method Auth Id save before delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($resource) {
            $resource->deleted_by = \Auth::user()->id;
            $resource->save();
        });
    }

    /**
     * Get Descendant Files (till 3rd level) [temporary solution]
     * @param  int $rootId | Root Id
     * @return Query
     */
    protected function descendantFiles($rootId)
    {
        $parentIds[] = $rootId;
        $firstLevel = self::whereParentId($rootId)->dir()->pluck('id')->toArray();
        $parentIds = array_merge($parentIds, $firstLevel);
        $secondLevel = self::whereIn('parent_id', $firstLevel)->dir()->pluck('id')->toArray();
        $parentIds = array_merge($parentIds, $secondLevel);
        $thirdLevel = self::whereIn('parent_id', $secondLevel)->dir()->pluck('id')->toArray();
        $parentIds = array_merge($parentIds, $thirdLevel);
        return self::whereIn('parent_id', $parentIds)->file();
    }
}
