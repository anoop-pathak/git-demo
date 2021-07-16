<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Interfaces\ResourceModelInterface;
use \Franzose\ClosureTable\Models\Entity;
use App\Traits\ClosureTableTrait;
use App\Models\ResourceBaseModel;
use Request;
use Queue;
use FlySystem;
use Illuminate\Support\Facades\Auth;

class Resource extends ResourceBaseModel implements ResourceModelInterface
{
    use SoftDeletes;
    // use ClosureTableTrait;
    public $timestamps = true;
    protected $fillable = ['name','company_id','size','thumb_exists','path','is_dir','mime_type','locked','created_by','parent_id', 'admin_only', 'user_id', 'reference_id','multi_size_image','external_full_url',
    ];
    protected $dates = ['deleted_at'];
    protected $appends = ['children', 'is_expired', 'expiration_id', 'expiration_date', 'url', 'thumb_url', 'multi_size_images', 'original_file_path', 'relative_path'];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'new_resources';
    /**
     * ClosureTable model instance.
     *
     * @var resourceClosure
     */
    protected $closure = '\resourceClosure';
    protected static $createDirRules = [
        'parent_id'  =>  'required|integer',
        'name'       =>  'required|regex:/^(\w+\s\.?)*\w+$/',
    ];
    public static function getCreateDirRules() {
        return self::$createDirRules;
    }
    public static $copyRule = [
        'copy_to'      => 'required|integer',
        'resource_ids' => 'required'
    ];
    // resource directories..
    const SUBSCRIBER_RESOURCES = 'subscriber_resources';
    const EMAIL_ATTACHMENTS    = 'emails_attachments';
    const LABOURS              = 'labours';
    const INSTANT_PHOTO_DIR    = 'instant_photos';
    const SUB_CONTRACTOR_DIR   = 'sub_contractor';
    const SUB_CONTRACTOR_INVOICES   = 'invoices';
    const GOOGLE_DRIVE_LINK    = 'google_drive_link';
    const PHONE_MESSAGES_MEDIA = 'phone_messages_media';
    // const HOME_OWNER_PAGE      = 'Home Owner Page';
    const VENDOR_BILL_ATTACHMENTS = 'vendor_bill_attachments';
	const ATTACHMENTS = 'attachments';
	const DRIP_CAMPAIGN_EMAIL_ATTACHMENT = 'drip_campaign_email_attachment';
	const JOB = 'jobs';

    protected static $uploadFileRules = [
        'parent_id'       =>  'required|integer',
        'image_base_64'   =>  'nullable|boolean'
    ];
    public static function uploadFileRules() {
        $rules = self::$uploadFileRules;
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize;

        if (Request::has('image_base_64')) {
            $rules['file'] = 'required';
        }

        return $rules;
    }
    protected static $resourcesRules = [
        'parent_id'  =>  'required|integer',
    ];
    public static function getReourcesRules() {
        return self::$resourcesRules;
    }
    protected static $renameRules = [
        'id' => 'required|integer',
        'name' => 'required',
    ];
    public static function getRenameRules() {
        return self::$renameRules;
    }
    protected static $getFileRules = [
        'id' => 'required|integer',
    ];
    protected function getMoveRules()
    {
        return [
            'move_to'      => 'required|integer',
            'resource_id'  => 'required_without:resource_ids',
            'resource_ids' => 'max_array_size:'.config('jp.image_multi_select_limit')
        ];
    }
    public static function getFileRules() {
        return self::$getFileRules;
    }
    public static function getCopyRules()
    {
        return self::$copyRule;
    }
    protected function getOnlyFileUploadRule()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules = ['file' => 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize];
        return $rules;
    }
    protected function getOpenAPIFileUploadRule()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules = [
            'file_name' => 'max:30'
        ];

        if(!Request::get('file_url')) {
            $rules['file'] = 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize;
        }

        return $rules;
    }
    public static function getNameAttribute($value) { 
        return str_replace(' ', '_', $value);
    }
    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function getIsExpiredAttribute()
    {
        if(!$documentExpire = $this->documentExpire) {
            return 0;
        }
        $expiredDateObject = new Carbon($documentExpire->expire_date);
        $todayDateObject = Carbon::now();
        return  (int)$todayDateObject->gt($expiredDateObject);
    }
    public function getExpirationIdAttribute()
    {
        return ($document = $this->documentExpire) ? $document->id : null;
    }
    public function getExpirationDateAttribute()
    {
        return ($document = $this->documentExpire) ? $document->expire_date : null;
    }
    public function getURlAttribute()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) {
            return $this->path;
        }
        if($this->multi_size_image) {
            return FlySystem::publicUrl(config('resources.BASE_PATH').preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_pv1024$1', $this->path));
        }
        return FlySystem::publicUrl(config('resources.BASE_PATH').$this->path);
    }
    public function getThumbURlAttribute()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) {
            return $this->thumb;
        }
        if(in_array($this->mime_type, config('resources.image_types'))) {
            if(!$this->thumb_exists) {
                return config('app.url').'generating-thumb.jpg';
            }
            // add thumb suffix in filename for thumb name
            return preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $this->original_file_path);
        }
        return null;
    }

    public function getThumbSignedURlAttribute()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) {
            return $this->thumb;
        }
        if(in_array($this->mime_type, config('resources.image_types'))) {
            if(!$this->thumb_exists) {
                return config('app.url').'generating-thumb.jpg';
            }
            // add thumb suffix in filename for thumb name
            return \FlySystem::getAwss3SignedUrl(preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', config('resources.BASE_PATH').$this->path));

        }
        return null;
    }
    public function getMultiSizeImagesAttribute()
    {
        $images = [];
        if(in_array($this->mime_type, config('resources.image_types'))) {
            if (!$this->multi_size_image) {
                Queue::push('App\Handlers\Events\ResourceQueueHandler@createMultiSizeImage', ['id' => $this->id]);
                return $images;
            }
            $sizes = config('resources.multi_image_width');
            foreach ($sizes as $size) {
                $images[] = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', "_pv{$size}$1", $this->original_file_path);
            }
        }
        return $images;
    }
    public function getOriginalFilePathAttribute()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) {
            return $this->path;
        }
        return FlySystem::publicUrl(config('resources.BASE_PATH').$this->path);
    }

    public function getRelativePathAttribute()
	{
		return config('resources.BASE_PATH').$this->path;
    }

    public function getSignedUrlAttribute()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) {
            return $this->path;
        }
        return \FlySystem::getAwss3SignedUrl(config('resources.BASE_PATH').$this->path);
    }

    public function meta() {
        return $this->hasMany(ResourceMeta::class);
    }

    public function documentExpire()
    {
        return $this->hasOne(DocumentExpirationDate::class, 'object_id', 'id')
            ->whereObjectType(DocumentExpirationDate::RESOURCE_OBJECT_TYPE);
    }

    public function allChildren() {
        return $this->hasMany(Resource::class,'parent_id', 'id');
    }

    public function parent() {
        return $this->belongsTo(Resource::class, 'parent_id', 'id')->withTrashed();
    }

    public function isAdminOnly()
    {
        return ($this->admin_only);
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

    public function scopeName($query,$name) {
        return $query->where('name',$name);
    }

    public function scopeCompany($query, $companyId) {
        return $query->where('company_id',$companyId);
    }

    public static function companyRoot($companyId) {
        return self::where('company_id',$companyId)
            ->where('parent_id',Null)->first();
    }

    public function scopeLocked($query) {
        return $query->where('locked',true);
    }

    public function scopeExcludeAdminOnlyDirectory($query)
    {
        return $query->whereAdminOnly(false);
    }

    public function scopeDateRange($query,$start = null ,$end = null)
    {
        if($start) {
            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('created_at').", '%Y-%m-%d') >= '$start'");
        }
        if($end) {
            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('created_at').", '%Y-%m-%d') <= '$end'");
        }
    }

    public function scopeDate($query, $date)
    {
        $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('created_at').", '%Y-%m-%d') = '$date'");
    }

    // sub contractor scope
    public function scopeSubOnly($query, $subId)
    {
        $query->whereCreatedBy($subId);
    }

    public function getChildrenAttribute()
    {
        return [];
    }

    /**
     * **
     * @method Auth Id save before delete
     */
     public static function boot(){
        parent::boot();
        static::deleting(function($resource){
            $resource->deleted_by = Auth::user()->id;
            $resource->save();
        });
     }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new \App\Services\Resources\ResourceCollection($models);
    }

    public function isGoogleDriveLink()
    {
        if ($this->type == self::GOOGLE_DRIVE_LINK) return true;
    }

    /**
     * Get Descendant Files (till 3rd level) [temporary solution]
     * @param  int $rootId | Root Id
     * @return Query
     */
    protected function descendantFiles($rootId)
    {
        $parentIds[] = $rootId;
        $firstLevel  = self::whereParentId($rootId)->dir()->pluck('id')->toArray();
        $parentIds   = array_merge($parentIds,$firstLevel);
        $secondLevel = self::whereIn('parent_id',$firstLevel)->dir()->pluck('id')->toArray();
        $parentIds   = array_merge($parentIds,$secondLevel);
        $thirdLevel  = self::whereIn('parent_id',$secondLevel)->dir()->pluck('id')->toArray();
        $parentIds   = array_merge($parentIds,$thirdLevel);
        return self::whereIn('parent_id', $parentIds)->file();
    }

    protected function toHierarchy($collection)
    {
        $data = [];
        foreach ($collection as $key => $item) {
            $data[$item->id] = $item;
        }
        return $data;
    }
}
