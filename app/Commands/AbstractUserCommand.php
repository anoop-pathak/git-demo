<?php

namespace App\Commands;

abstract class AbstractUserCommand
{

    /**
     * array of all fields submitted
     * @var Array
     */
    public $input;

    /**
     * array of User fields
     * @var Array
     */

    public $userData;

    /**
     * array of user profile fields
     * @var Array
     */

    public $userProfileData;

    /**
     * Array of departments
     * @var Array
     */
    public $departments;

    /**
     * group id
     * @var int
     */
    public $group;

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input)
    {
        $this->input = $input;
        $this->extractInput();
    }

    /**
     * Extract input to respective Model
     * @return void
     */
    private function extractInput()
    {
        $this->mapUserInput();
        $this->mapUserProfileInput();
        $this->roles = isset($this->input['roles']) ? $this->input['roles'] : null;
        if (ine($this->input, 'group_id')) {
            $this->group = (int)$this->input['group_id'];
        }

        if (!ine($this->userData, 'hire_date')) {
            $this->userData['hire_date'] = null;
        }
    }

    /**
     * Map User Model inputs
     * @return void
     */
    private function mapUserInput()
    {
        $map = [
            'first_name',
            'last_name',
            'group_id',
            'admin_privilege',
            // labour , sub contractor, etc
            'hire_date',
            'company_name',
            'note',
            'rating',
        ];

        $this->userData = $this->mapInputs($map);
    }

    /**
     * Map  UserProfile Model inputs
     * @return void
     */
    private function mapUserProfileInput()
    {
        $map = [
            'phone' => 'phone',
            'cell',
            'address',
            'address_line_1',
            'city',
            'state_id',
            'country_id',
            'zip',
            'position',
            'additional_phone',
        ];
        $this->userProfileData = $this->mapInputs($map);
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map)
    {
        $ret = [];
        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($this->input[$value]) ? $this->input[$value] : "";
            } else {
                $ret[$key] = isset($this->input[$value]) ? $this->input[$value] : "";
            }
        }

        return $ret;
    }
}
