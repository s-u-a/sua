<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/
	/**
	 * @author Candid Dauth
	 * @package sua
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Einheitliche Funktionen, um mit bestimmten Objekten aus der Datenbank wie
	 * Benutzern, Nachrichten oder Allianzen umzugehen.
	*/

	abstract class Dataset implements Singleton
	{
		private $name;
		protected $params;
		private static $setdatabase_handlers = array();

		/** Die Datenbank, in der die Dataset-Objekte gespeichert werden.
		  * @var Database */
		private static $database = null;

		/**
		 * Erzeugt ein Datenset mit dem Namen $name.
		 * @param $name string Null, wenn ein neuer Name erzeugt werden soll.
		 * @return String Die ID des neuen Objekts (normalerweise datasetName($name))
		*/
		abstract static public function create($name=null);

		/**
		 * Löscht das instanzierte Datenset aus der Datenbank.
		 * @return void
		*/
		abstract public function destroy();

		/**
		 * Gibt eine Liste aller Datensets des implementierenden Typs zurück.
		 * @return array(string)
		*/
		abstract static public function getList();

		/**
		 * Gibt die Anzahl existierender Sets des implementierenden Typs zurück (count(getList())).
		 * @return int
		*/
		abstract static public function getNumber();

		/**
		 * Gibt zurück, ob das angegebene Datenset existiert.
		 * @return bool
		*/
		abstract static public function exists($name);

		static public function datasetName($name=null)
		{
			if($name === "0")
				return "O";
			else
				return $name;
		}

		/**
		 * @param $name Die ID des zu instanzierenden Datensets.
		*/

		public function __construct($name=null)
		{
			$name = static::idFromParams(func_get_args());

			$name = static::datasetName($name);
			$this->name = $name;
			$this->params = static::paramsFromId($name);
			if(!static::exists($name))
				throw new DatasetException("Dataset does not exist.");
		}

		/**
		 * Gibt den Namen dieses Datensets zurück.
		 * @return string
		*/

		function getName()
		{
			return $this->name;
		}

		/**
		 * Hilft Datensets, die mehrere Parameter in Kombination zur Identifikation
		 * verwenden (zum Beispiel Planeten mit drei Koordinaten). Diese Funktion
		 * konvertiert mehrere Parameter zu einer ID, mit der die anderen Funktionen
		 * problemlos ausgeführt werden. Jede Datensetfunktion, die eine ID übergeben
		 * bekommt, sollte diese vorher aus func_get_args() mit dieser Funktion
		 * (static::) auflösen.
		 * @param array $params
		 * @return string
		*/

		static function idFromParams(array $params)
		{
			if(isset($params[0]))
				return $params[0];
			else
				return null;
		}

		/**
		 * Umkehrfunktion von idFromParams().
		 * @param string $id
		 * @return array
		*/

		static function paramsFromId($id)
		{
			return array($id);
		}

		/**
		 * Wird von Kind-Klassen aufgerufen, wenn das Datenset umbenannt wird. Der Rückgabewert von getName()
		 * wird damit aktualisiert.
		 * @param string $new_name
		 * @return void
		*/

		protected function rename($new_name)
		{
			$this->name = $new_name;
		}

		/**
		 * Setzt die Datenbank, in der die Datasets alle liegen, da es viel zu umständlich ist, für jedes
		 * Dataset eine Datenbank mitzugeben. Kann im Moment nur einmal ausgeführt werden.
		 * @param Database $database
		 * @return void
		*/

		public static final function setDatabase(Database $database)
		{
			if(self::$database)
				throw new DatasetException("The database may only be set once.");
			self::$database = $database;
			foreach(self::$setdatabase_handlers as $handler)
				$handler();
		}

		/**
		 * Gibt die Datenbank zurück, die mit Dataset::setDatabase() gesetzt wurde.
		 * @throw DatasetException Es wurde noch keine Datenbank gesetzt. (DatasetException::NO_DATABASE)
		 * @return void
		*/

		public static final function getDatabase()
		{
			if(!self::$database)
				throw new DatasetException("No database has been set.", DatasetException::NO_DATABASE);
			return self::$database;
		}

		/**
		 * Fügt eine Funktion hinzu, die ausgeführt werden soll, wenn Dataset::setDatabase() ausgeführt
		 * wird.
		 * @param callback $handler
		 * @return void
		*/

		public static final function addDatabaseChangeListener($handler)
		{
			self::$setdatabase_handlers[] = $handler;
		}
	}