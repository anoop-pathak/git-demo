<?php  namespace App\Services\Grid;


trait CommanderTrait
{
    /**
     * Execute the command
     *
     * @param  string $command
     * @param  array $input
     * @param  array $decorators
     * @return mixed
     */
    public function execute($command, array $input = null)
    {
        $command = new $command($input);

        return $command->handle();
    }
}

