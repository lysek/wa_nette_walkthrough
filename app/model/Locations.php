<?php namespace App\Model;

use Nette;

class Locations {

	use Nette\SmartObject;

	private $database;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
    }

	function getLocations() {
		return $this->database->table('locations')
            ->order('city');
	}

}
