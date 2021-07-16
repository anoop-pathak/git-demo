<?php

namespace App\Models;

class QuickbookMeta extends BaseModel
{

    protected $table = 'quickbook_meta';

    protected $fillable = ['name', 'qb_desktop_username', 'qb_desktop_id', 'type'];
}
