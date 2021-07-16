<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ContactNote extends BaseModel
{
    use SortableTrait;
    use SoftDeletes;

    protected $table = 'contact_notes';

	protected $fillable = ['company_id', 'contact_id', 'note', 'created_by'];

    protected $rules = [
        'notes' => 'required|array',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

     public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contact()
    {

        return $this->belongsTo(Contact::class);
    }

    public function company()
    {

        return $this->belongsTo(Company::class);
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function($model) {
            if(Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function($model) {
            if(Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }
}