<?php

namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use QuickBooks_XML_Parser;
use App\Models\QBDUnitOfMeasurement;


class UnitOfMeasurement extends BaseEntity
{
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function getJpEntityByQbdId($id)
    {
        return QBDUnitOfMeasurement::withTrashed()->where('qb_desktop_id', $id)
            ->where('company_id', '=', getScopeId())
            ->first();
    }

    public function getJpEnity($id)
    {
        return QBDUnitOfMeasurement::withTrashed()->where('id', $id)->first();
    }

    public function parse($xml)
    {
        $errnum = 0;

        $errmsg = '';

        $Parser = new QuickBooks_XML_Parser($xml);

        $map = [];

        if ($Doc = $Parser->parse($errnum, $errmsg)) {

            $Root = $Doc->getRoot();

            $List = $Root->getChildAt('QBXML/QBXMLMsgsRs/UnitOfMeasureSetQueryRs');

            foreach ($List->children() as $item) {

                $map = [
                    'ListID' => $item->getChildDataAt('UnitOfMeasureSetRet ListID'),
                    'EditSequence' => $item->getChildDataAt('UnitOfMeasureSetRet EditSequence'),
                    'TimeCreated' =>  $item->getChildDataAt('UnitOfMeasureSetRet TimeCreated'),
                    'TimeModified' =>  $item->getChildDataAt('UnitOfMeasureSetRet TimeModified'),
                    'Name' =>  $item->getChildDataAt('UnitOfMeasureSetRet Name'),
                    'IsActive' => $item->getChildDataAt('UnitOfMeasureSetRet IsActive'),
                    'UnitOfMeasureType' =>  $item->getChildDataAt('UnitOfMeasureSetRet UnitOfMeasureType'),
                    'BaseUnit' => [
                        'Name' => $item->getChildDataAt('UnitOfMeasureSetRet BaseUnit Name'),
                        'Abbreviation' => $item->getChildDataAt('UnitOfMeasureSetRet BaseUnit Abbreviation'),
                    ],
                ];
            }
        }

        return $map;
    }

    function create($qbdUnitOfMeasure)
    {
        $mappedInput = $this->reverseMap($qbdUnitOfMeasure);

        $unit = $this->saveOrUpdateUnit($mappedInput);

        $this->linkEntity($unit, $qbdUnitOfMeasure);

        return $unit;
    }

    function update($qbdUnitOfMeasure, QBDUnitOfMeasurement $unit)
    {
        $mappedInput = $this->reverseMap($qbdUnitOfMeasure);

        $unit = $this->saveOrUpdateUnit($mappedInput);

        $this->linkEntity($unit, $qbdUnitOfMeasure);

        return $unit;
    }

    public function reverseMap($input, QBDUnitOfMeasurement $unit = null)
    {
        $mapInput = [
            'qb_desktop_id' => $input['ListID'],
            'qb_desktop_sequence_number' => $input['EditSequence'],
            'object_last_updated' => $input['EditSequence'],
            'name' =>  $input['Name'],
            'type' => $input['UnitOfMeasureType'],
            'is_active' => $input['IsActive'],
            'base_unit_name' => $input['BaseUnit']['Name'],
            'base_unit_abbreviation' => $input['BaseUnit']['Abbreviation']
        ];

        if ($unit) {
            $mapInput['id'] = $unit->id;
        }

        return $mapInput;
    }

    public function saveOrUpdateUnit($mapInput)
    {
        $data = [
            'company_id' => getScopeId(),
            'qb_desktop_id' => $mapInput['qb_desktop_id']
        ];

        if(ine($mapInput, 'id')) {
            $data['id'] = $mapInput['id'];
        }

        $unit = QBDUnitOfMeasurement::firstOrNew($data);

        $unit->name = $mapInput['name'];
        $unit->type = $mapInput['type'];
        $unit->base_unit_name = $mapInput['base_unit_name'];
        $unit->base_unit_abbreviation = $mapInput['base_unit_abbreviation'];
        $unit->save();

        return $unit;
    }
}
