<?php

namespace App\Models;

class JobMeta extends BaseModel
{

    protected $table = 'job_meta';

    protected $fillable = ['job_id', 'meta_value', 'meta_key'];

    // const HOME_OWNER_DIR = 'home_owner_dir';
    const DEFAULT_PHOTO_DIR = 'default_photo_dir';
    const COMPANY_CAM_ID = 'company_cam_id';
}
