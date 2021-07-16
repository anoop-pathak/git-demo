<?php namespace App\Services\QuickBooks\Entity;

use QuickBooksOnline\API\Data\IPPIntuitEntity;

trait DisplayNameTrait {

    private function extractNameParts(IPPIntuitEntity $entity){

        $display_parts = explode(" ", $entity->DisplayName, 2);

        $first_name = ($entity->GivenName) ? $entity->GivenName : $display_parts[0];
        $last_name = $entity->FamilyName;

        if(!$last_name){
            $last_name = (count($display_parts) == 1) ? $display_parts[0] : $display_parts[1];
        }

        return [$first_name, $last_name, $entity->DisplayName];
    }


    private function sanitizeFirstName($first_name){
        return substr($first_name, 0, 25);
    }

    private function sanitizeLastName($last_name){
        return substr($last_name, 0, 25);
    }

    private function sanitizeDisplayName($displayName){
        return removeQBSpecialChars(substr($displayName, 0, 100));
    }

}