<?php
namespace App\Services\QuickBookDesktop\Traits;

trait DisplayNameTrait
{
    private function extractNameParts($entity)
    {

        $display_parts = explode(" ", $entity['Name'], 2);

        $first_name = ($entity['FirstName']) ? $entity['FirstName'] : $display_parts[0];

        $last_name = $entity['LastName'];

        if (!$last_name) {
            $last_name = (count($display_parts) == 1) ? $display_parts[0] : $display_parts[1];
        }

        return [
            $first_name, $last_name, $entity['Name']
        ];
    }


    private function sanitizeFirstName($first_name)
    {
        return substr($first_name, 0, 25);
    }

    private function sanitizeLastName($last_name)
    {
        return substr($last_name, 0, 25);
    }

    private function sanitizeDisplayName($name)
    {
        return removeQBSpecialChars(substr($name, 0, 100));
    }
}