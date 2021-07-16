<?php namespace App\Services\Contexts;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Settings;

class CompanyContext implements Context
{

    /**
     * The current context
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    protected $context;

    /**
     * Set the context
     *
     * @param Illuminate\Database\Eloquent\Model
     */
    public function set(Model $context)
    {
        if (function_exists('newrelic_add_custom_parameter')) {
            newrelic_add_custom_parameter ('companyID', $context->id);
        }

        $this->context = $context;
    }

    /**
     * Check to see if the context has been set
     *
     * @return boolean
     */
    public function has()
    {
        if ($this->context) {
            return true;
        }

        return false;
    }

    /**
     * Get the context identifier
     *
     * @return integer
     */
    public function id()
    {
        return $this->context->id;
    }

    /**
     * Get the context column
     *
     * @return string
     */
    public function column()
    {
        return 'company_id';
    }

    /**
     * Get the context table name
     *
     * @return string
     */
    public function table()
    {
        return 'companies';
    }

    /**
    * Get object of context
    *
    * @return object
    */
    public function get()
    {
        return $this->context;
    }

    public function getSinceInceptionDate()
    {
        $context = $this->get();

        if(!$context) {
            return false;
        }

        //Since Inception
        $date = Carbon::parse($context->created_at, 'UTC');

        if(Settings::get('TIME_ZONE')) {
            $date->setTimezone(Settings::get('TIME_ZONE'));
        }

        return $date->toDateString();
    }
}
