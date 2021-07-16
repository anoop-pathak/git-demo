<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;

class QBEntityError extends BaseModel
{
	use SortableTrait;

    const DUPLICATE_ERROR_CODE = 'duplicate_customer';

    protected $table = 'qb_entity_errors';

	protected $fillable = ['company_id', 'entity_id', 'entity', 'message', 'details', 'error_code', 'error_type', 'meta'];

}