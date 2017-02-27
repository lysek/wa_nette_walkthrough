<?php namespace App\Model;

use Nette;

class Persons {

	use Nette\SmartObject;

	private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

	public function add($values) {
		return $this->database->table('persons')->insert($values);
	}

    public function getPersons() {
        return $this->database->table('persons')->order('last_name DESC');
    }

	public function delete($id) {
		return $this->database->table('persons')->where("id = ?", $id)->delete();
	}

}

