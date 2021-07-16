<?php

namespace App\Traits;

trait ExecutableCommandTrait
{
	public function executeCommand($command, $data) {
        $command = new $command($data);

        return $command->handle();
    }
}