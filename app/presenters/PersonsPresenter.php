<?php

namespace App\Presenters;

use App\Model\Persons;
use Nette;

class PersonsPresenter extends Nette\Application\UI\Presenter
{

	private $personsModel;

	public function __construct(Persons $pm) {
		parent::__construct();

		$this->personsModel = $pm;
	}

	function renderDefault() {
		$this->template->persons = $this->personsModel->getPersons();
	}

}