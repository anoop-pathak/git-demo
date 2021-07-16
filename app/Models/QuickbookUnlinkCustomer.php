<?php
namespace App\Models;

class QuickbookUnlinkCustomer extends BaseModel
{
	const QBO 	= 'qbo';
	const QBD 	= 'qbd';

    protected $fillable = ['customer_id', 'quickbook_id', 'company_id', 'created_by', 'type'];

 }