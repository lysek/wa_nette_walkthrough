# Ukázka použití frameworku Nette

Tento projekt vznikl pro předmět WA na PEF MENDELU. Tento průvodce ukáže základní použití frameworku Nette pro
vytvoření aplikace, která zhruba odpovídá části zadání z předmětu APV (tedy evidence osob a jejich adres).

## Úvod

### Platné pro:
- Nette 2.4.x
- Apache 2.x a PHP 7.0
- NetBeans (volitelné)

### Co je nutné udělat před vlastní prací
- nainstalovat [Composer](https://getcomposer.org/) tak, aby šel spouštět příkazem `composer` z příkazového řádku
- volitelně i [Git](https://git-scm.com/)

## Vlastní walkthrough

### Instalace a zahájení projektu
- vytvořte si složku pro projekt
- stáhněte framework pomocí Composeru příkazem `composer create-project nette/web-project .` (vč. tečky na konci)
- nyní je dobré založit projekt v NetBeans nebo jiném IDE
- na lokálním webovém serveru zkontrolovat, že aplikace běží (otevřít složku [http://locahost/nette_demo/www](http://locahost/nette_demo/www),
  mělo by se zobrazit jméno frameworku s odkazem na dokumentaci - welcome obrazovka). Pozor: Nette je nastaveno tak, že
  složka s aplikací není viditelná, je možné že se do ní nepůjde dostat (`/.htaccess`: `Order Allow,Deny`
  a `Deny from all` - zakomentovat a také v `/www/.htaccess`).

Může být nutné nastavit direktivu `RewriteBase` v souboru `/www/.htaccess` na aktuální cestu k aplikaci
např. `RewriteBase /~user/aplikace/www`.

Důležité adresáře a soubory:
- `/app`
	- `/config` - zde je konfigurace aplikace ve formátu Neon (variace na YAML)
		- `config.neon` - společná konfigurace
		- `config.local.neon` - lokální konfigurace pro konkrétní stroj (např. přístup k DB)
	- `/presenters` - složka pro presentery
		- `/templates` - složka s šablonami, pro každý presenter se zde vytvoří složka, výchozí soubor je default
	- `/router`
		- `RouterFactory.php` - routing aplikace
- `/log` - logy aplikace, tuto složku občas promazat (např. před odevzdáním nebo nahráním)
- `/temp` - dočasné soubory
- `/www` - veřejná část aplikace, to co je opravdu přístupné přes HTTP

### Volitelné: Git
Nyní je dobrá chvíle udělat první commit do Gitu. Spusťte `git init` a do `.gitignore` přidejte složku `/vendor` a `/nbproject`,
nebo jinou podle vašeho IDE. Soubor `.gitignore` už je v projektu nachystán a měl by obsahovat cesty k adresářům,
které framework nepotřebuje verzovat. Práci commitujte průběžně příkazy `git add .`, `git commit -m "..."` a pokud máte i
vzdálený repositář, tak i `git push`.

### Databáze
Nejprve je nutné nastavit připojení k databázi v souboru `/app/config/config.local.neon`:

	database:
		dsn: 'mysql:host=127.0.0.1;dbname=wa_walkthrough_nette'
		user: login
		password: heslo

Databázi můžete naimportovat ze souboru [`/db_struktura.sql`](https://github.com/lysek/wa_nette_walkthrough/blob/master/db_struktura.sql).

### Výpis osob
Vložte pomocí Admineru nebo phpMyAdminu do databáze záznamy o nějakých osobách. Vytvoříme třídu pro presenter
`/app/presenters/PersonsPresenter.php` a šablonu `/app/presenters/templates/Persons/default.latte`, která data vypíše.
Šablona automaticky načte sobuor `@layout.latte`. Presenter dědí z třídy `Nette\Application\UI\Presenter`.
Je vidět, že šablony jsou uloženy vždy ve složce, která názvem odpovídá názvu presenteru (bez slova Presenter).

V routeru `/app/router/RouterFactory.php` nastavte akci `default` z `PersonsPresenter` jako výchozí a smažte
`HomepagePrsenter` a jeho šablony v odpovídající složce:

	$router[] = new Route('<presenter>/<action>[/<id>]', 'Persons:default');

Router je nastaven tak, že pro příchozí neznámou URL se snaží najít presenter a na něm spustit metodu.
Např.: `/persons/edit` by spustilo `renderEdit` na třídě `PersonsPresenter`. Metoda `render*` by měla vykreslovat
šablonu, ještě se často používá metoda `action*` pro akce, které přesměrovávají jinam, např. `actionDelete`.

Nyní vytvoříme model pro práci s osobami - soubor `/app/model/Persons.php`:

	<?php namespace App\Model;

	use Nette;

	class Persons {

		use Nette\SmartObject;

		private $database;

		public function __construct(Nette\Database\Context $database)
		{
			$this->database = $database;
		}

		public function getPersons()
		{
			return $this->database->table('persons')->order('last_name DESC');
		}

	}

Závislost na `Nette\Database\Context` bude automaticky dosazena přes dependency injection.
Do `/app/config/config.neon` přidáme řádek registrující model Persons do systému dependency injection (DI),
abychom mohli o model `Persons` žádat v konstruktoru `PersonsPresenter`.

	services:
		router: App\RouterFactory::createRouter
		- App\Model\Persons

Pozor: v souborech Neon je potřeba odsazovat pomocí mezer místo tabulátorů.

[Zdrojové kódy](https://github.com/lysek/wa_nette_walkthrough/commit/f8e74e0d4dbd891cde003e7f18d6a43b0642c57f)

V layout latte můžeme nachystat menu buď ručně nebo můžeme cesty vygenerovat makrem `{link ...}`:

	<nav>
		<a href="{$basePath}/persons">Výpis osob</a>
		<!-- nebo -->
		<a href="{link Persons:create}">Pridat osobu</a>
	</nav>

Proměnná `$basePath` je v Nette velice důležitá, protože obsahuje cestu k veřejné části aplikace, dá se tedy použít
pro načítání JS nebo CSS souborů v hlavičce.

### Přidání nové osoby
Přidáme do presenteru tovární metodu pro formulář s předvyplněnými lokalitami:

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

V šabloně stačí jen nechat formulář vyrenderovat z komponenty:

	{block title}Vytvoreni osoby{/block}
	{block content}
		{control personForm}
	{/block}

Formuláře v Nette přímo generují HTML strukturu do HTML tabulky. Následně přidáme metodu pro obsluhu odeslání,
která je zaregistrována na formuláři. Necháme zobrazit odpovídající flash zprávy:

	public function personFormSucceeded(UI\Form $form, $values) {
		try {
			$this->personsModel->add($values);
			$this->flashMessage('Osoba byla vlozena.');
			$this->redirect('Persons:default');
		} catch(UniqueConstraintViolationException $e) {
			$this->flashMessage('Takova osoba uz existuje.');
		}
    }

V layoutu aplikace je už nachystáno zobrazování flash zpráv, v případě chyby se formulář zobrazí znovu s flash zprávou.

[Zdrojové kódy](https://github.com/lysek/wa_nette_walkthrough/commit/ae22fa166509d64e171d209766656524451522f3)

### Smazání osoby
Do šablony vložíme formulář s JavaScriptovým potvrzením. Tento formulář bude vykreslen v každém řádku tabulky a odešle
ID osoby, která má být smazána.

Do atributu `action` formuláře buď opět vložíme přímo cestu k akci delete nebo ji necháme vygenerovat přes makro `link`:

	<form action="{$basePath}/persons/delete" onsubmit="return confirm('Opravdu smazat osobu?')" method="post">
		<input type="hidden" name="id" value="{$p->id}" />
		<input type="submit" value="Smazat" />
	</form>

	<form action="{link Persons:delete}" onsubmit="return confirm('Opravdu smazat osobu?')" method="post">
		<input type="hidden" name="id" value="{$p->id}" />
		<input type="submit" value="Smazat" />
	</form>

[Zdrojové kódy](https://github.com/lysek/wa_nette_walkthrough/commit/67d958eb34fe69f3ca301953a206fe85f5f75f3b)

## Poznámky

### Rozjetí projektu na jiném stroji (po stažení z Gitu)
Příkazem `git clone http://adresa.repositare.cz/nazev.git slozka` se vám stáhne z Gitu kopie projektu. Jelikož jsou
některé důležité soubory a složky nastavené v souboru `.gitignore`, je potřeba primárně spustit příkaz
`composer install`, aby se stáhl vlastní framework a jeho knihovny.