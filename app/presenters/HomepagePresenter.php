<?php

namespace App\Presenters;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\Identity;
use Tracy\Debugger;
use lookyman\U2fNette\Dialog\LoginDialog;
use lookyman\U2fNette\Dialog\RegisterDialog;

class HomepagePresenter extends Presenter
{

	/** string */
	const SESSION_NAMESPACE = 'loginU2f';

	/** @var \lookyman\U2fNette\Dialog\DialogFactory @inject */
	public $dialogFactory;

	/** @var \lookyman\U2fNette\User\IRegistrationRepository @inject */
	public $registrationRepository;

	/** @var int */
	private $id;

	public function actionClear()
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('You must be logged in.');
			$this->redirect('default');
		}
		$this->registrationRepository->clearRegistrations($this->getUser()->getId());
		$this->flashMessage('All registered U2F devices for current user have been deleted.');
		$this->redirect('default');
	}

	public function actionLogin()
	{
		if ($this->getUser()->isLoggedIn()) {
			$this->flashMessage('Already logged in.');
			$this->redirect('default');
		}
	}

	public function actionLoginU2f()
	{
		if ($this->getUser()->isLoggedIn()) {
			$this->flashMessage('Already logged in.');
			$this->redirect('default');
		}
		$this->id = $this->getSession(self::SESSION_NAMESPACE)->id;
	}

	public function actionLogout()
	{
		$this->getUser()->logout(TRUE);
		$this->flashMessage('You have been logged out.');
		$this->redirect('default');
	}

	public function actionRegisterU2f()
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->flashMessage('You must be logged in.');
			$this->redirect('default');
		}
	}

	public function loginFormSuccess(Form $form)
	{
		$values = $form->getValues();
		if ($values->email === 'u2f@example.org' && $values->password === 'u2f') {
			$userId = 1; // Get this from your user database.

			if (count($this->registrationRepository->findRegistrations($userId))) {
				$this->getSession(self::SESSION_NAMESPACE)->setExpiration('5 minutes')->id = $userId;
				$this->redirect('loginU2f');

			} else {
				$this->getUser()->login(new Identity($userId));
				$this->flashMessage('You have been logged in.');
				$this->redirect('default');
			}
		}
	}

	/**
	 * @return \Nette\Application\UI\Form
	 */
	protected function createComponentLoginForm()
	{
		$form = new Form;
		$form->addProtection();

		$form->addText('email', 'Email')
			->setRequired();
		$form->addPassword('password', 'Heslo')
			->setRequired();
		$form->addSubmit('submit');

		$form->onSuccess[] = [$this, 'loginFormSuccess'];

		$form->setDefaults([
			'email' => 'u2f@example.org',
			'password' => 'u2f',
		]);

		return $form;
	}

	/**
	 * @return \lookyman\U2fNette\Dialog\LoginDialog
	 */
	protected function createComponentLoginU2f()
	{
		$control = $this->dialogFactory->createLoginDialog($this->id);

		$control->onResponse[] = function (LoginDialog $dialog) {
			$this->getSession(self::SESSION_NAMESPACE)->remove();
			if ($dialog->getRegistration()) {
				$this->getUser()->login(new Identity($this->id));
				$this->flashMessage('Successful login with U2F.');

			} else {
				$this->flashMessage('Error during login.');
			}
			$this->redirect('default');
		};

		$control->onError[] = function (\Exception $e) {
			Debugger::log($e, 'login');
		};

		return $control;
	}

	/**
	 * @return \lookyman\U2fNette\Dialog\RegisterDialog
	 */
	protected function createComponentRegisterU2f()
	{
		$control = $this->dialogFactory->createRegisterDialog();

		$control->onResponse[] = function (RegisterDialog $dialog) {
			$this->flashMessage($dialog->getRegistration() ? 'Successful registration of U2F device.' : 'Error during registration.');
			$this->redirect('default');
		};

		$control->onError[] = function (\Exception $e) {
			Debugger::log($e, 'register');
		};

		return $control;
	}

}
