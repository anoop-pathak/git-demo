<?php
namespace App\Commands;

class CustomerResourceCommand
{
    public $stopDBTransaction = false;

	public function __construct( $input, $geocoding_required = true) {
		$this->input = $input;
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\CustomerResourceCommandHandler::class);

        return $commandHandler->handle($this);
    }

}