<?php

namespace App\Presenters;

use Nette\Object;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Responses\CallbackResponse;
use Tracy\ILogger;


class ErrorPresenter extends Object implements IPresenter
{

	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger)
	{
		$this->logger = $logger;
	}

	public function run(Request $request)
	{
		return new CallbackResponse(function () {
			require __DIR__ . '/templates/Error/error.phtml';
		});
	}

}
