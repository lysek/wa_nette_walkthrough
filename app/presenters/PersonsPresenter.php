<?php

namespace App\Presenters;

use App\Model\Locations;
use App\Model\Persons;
use Nette;
use Nette\Application\UI;
use Nette\Database\UniqueConstraintViolationException;

class PersonsPresenter extends Nette\Application\UI\Presenter
{

	private $personsModel;
	private $locationsModel;

	public function __construct(Persons $pm, Locations $lm) {
		parent::__construct();

		$this->personsModel = $pm;
		$this->locationsModel = $lm;
	}

	function renderDefault() {
		$this->template->persons = $this->personsModel->getPersons();
	}

	protected function createComponentPersonForm() {
		$locations = $this->locationsModel->getLocations();

		$locationsArr = [];
		foreach($locations as $loc) {
			$locationsArr[$loc->id] = $loc->city . ", " . $loc->street_name . " " . $loc->street_number;
		}
		$form = new UI\Form;
        $form->addText('first_name', 'Jmeno')->setRequired();
		$form->addText('last_name', 'Prijmeni')->setRequired();
		$form->addText('nickname', 'Prezdivka')->setRequired();
		$form->addSelect('id_location', "Adresa", $locationsArr)->setPrompt('Neznama adresa');;
		$form->addSubmit("add_person", 'Pridat osobu');
        $form->onSuccess[] = [$this, 'personFormSucceeded'];
        return $form;
    }

    public function personFormSucceeded(UI\Form $form, $values) {
		try {
			$this->personsModel->add($values);
			$this->flashMessage('Osoba byla vlozena.');
			$this->redirect('Persons:default');
		} catch(UniqueConstraintViolationException $e) {
			$this->flashMessage('Takova osoba uz existuje.');
		}
    }

}