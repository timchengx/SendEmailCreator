<?php

namespace Kanboard\Plugin\SendEmailCreator;

use Kanboard\Core\Plugin\Base;
use Kanboard\Model\CommentModel;
use Kanboard\Plugin\SendEmailCreator\Action\SendTaskEmailStart;
use Kanboard\Core\Translator;

class Plugin extends Base

{

	public function initialize()

	{

		$this->actionManager->register(new SendTaskEmailStart($this->container));

		$this->eventManager->register(CommentModel::EVENT_CREATE, 'On comment creation');
	}

	public function onStartup()
	{
		Translator::load($this->languageModel->getCurrentLanguage(), __DIR__ . '/Locale');
	}


	public function getPluginName()
	{
		return 'Email Task Start';
	}

	public function getPluginAuthor()
	{
		return 'Tim Cheng';
	}

	public function getPluginVersion()
	{
		return '1.0.0';
	}

	public function getPluginDescription()
	{
		return 'Send mail to specific person when a task start date occur';
	}

	public function getPluginHomepage()
	{
		return 'https://github.com/timchengx/SendEmailCreator';
	}
}
