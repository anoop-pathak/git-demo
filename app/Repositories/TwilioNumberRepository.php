<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\TwilioNumber;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\TwilioException;

Class TwilioNumberRepository extends ScopedRepository{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(TwilioNumber $model, Context $scope){
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Get Phone Twilio Number for the current user
     * @param $userId
     * @return object
     */
    public function getNumber($userId)
    {
        $phoneNumber = $this->make()->where('user_id', $userId)->first();

        return $phoneNumber;
    }

    /**
     * create phoneNumber
     * @return message
     */
    public function saveNumberInDB($phoneNumber, $metaInputs = [])
    {
        $phoneNumberArr = $phoneNumber->toArray();
        if(!$phoneNumberArr) {
            throw new TwilioException(trans('response.error.number_not_generated'));
        }

        $data = array(
            'company_id'   => getScopeId(),
            'user_id'      => Auth::id(),
            'phone_number' => $phoneNumberArr['phoneNumber'],
            'sid'          => $phoneNumberArr['sid'],
            'state_code'   => ine($metaInputs, 'state_code') ? $metaInputs['state_code'] : null,
            'zip_code'     => ine($metaInputs, 'zip_code') ? $metaInputs['zip_code'] : null,
            'lat'          => ine($metaInputs, 'lat') ? $metaInputs['lat'] : null,
            'long'         => ine($metaInputs, 'long') ? $metaInputs['long'] : null,
        );

    	$numberObj = TwilioNumber::create($data);

        return $numberObj;
    }
}
