<?php namespace App\Services\Contexts;

use Illuminate\Database\Eloquent\Model;

interface Context
{

    /**
     * Set the context
     *
     * @param Illuminate\Database\Eloquent\Model
     */
    public function set(Model $context);

    /**
     * Check to see if the context has been set
     *
     * @return boolean
     */
    public function has();

    /**
     * Get the context identifier
     *
     * @return integer
     */
    public function id();

    /**
     * Get the context column
     *
     * @return string
     */
    public function column();

    /**
     * Get the context table name
     *
     * @return string
     */
    public function table();

    /**
     * Get object of context
     *
     * @return object
     */
    public function get();

    /**
     * Get Inception Date
     *
     * @return object
     */
    public function getSinceInceptionDate();
}
