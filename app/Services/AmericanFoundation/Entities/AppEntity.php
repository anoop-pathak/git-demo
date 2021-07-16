<?php

namespace  App\Services\AmericanFoundation\Entities;

class AppEntity
{

    protected $companyId;
    protected $groupId;
    protected $csv_filename;

	/**
	 * Set csv file name from which data is importing.
	 *
	 * @param string $value
	 * @return self
	 */
	public function setCsvFileName($value)
	{
		$this->csv_filename = $value;
		return $this;
    }

	/**
	 * Set company id.
	 *
	 * @param integer $value
	 * @return self
	 */
    public function setCompanyId($value)
	{
		$this->companyId = $value;
		return $this;
	}

	/**
	 * Set group id.
	 *
	 * @param integer $value
	 * @return self
	 */
    public function setGroupId($value)
	{
		$this->groupId = $value;
		return $this;
	}
}