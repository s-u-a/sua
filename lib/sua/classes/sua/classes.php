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
	 * Stellt statische Funktionen zur Verfügung, die als Wrapper für das new-Keyword gelten.
	 * Auf diese Weise kann für Klassen, die das Interface Singleton implementieren, ein Mechanismus
	 * implementiert werden, der dafür sorgt, dass zum Beispiel für einen einzelnen Benutzer immer
	 * nur ein Objekt gleichzeitig existiert.
	*/

	class Classes
	{
		/**
		 * Speichert die Instanzen der Singleton-Variablen. Format: ( Klassenname => ( Singleton::datasetName() => Objekt ) )
		*/
		private static $objectInstances = array();

		/**
		 * Liefert eine Instanz der Klasse $classname mit dem Parameter $p1 zurück.
		 * $p1 wird zunächst durch Singleton::datasetName() gejagt. Existiert bereits
		 * eine Instanz von $classname mit $p1, wird diese zurückgegeben, ansonsten
		 * wird eine erzeugt.
		 * @param string $classname
		 * @param array $args
		 * @return Object
		*/

		static function __callStatic($classname, $args=array())
		{
			$classname_full = $classname[0] == "\\" ? $classname : "\\sua\\".$classname;

			if(in_array("sua\\Singleton", class_implements($classname_full)))
			{
				if(!isset(self::$objectInstances)) self::$objectInstances = array();
				if(!isset(self::$objectInstances[$classname])) self::$objectInstances[$classname] = array();
				$p1 = $classname_full::idFromParams($args);
				if(!isset(self::$objectInstances[$classname][$p1]))
				{
					$instance = new $classname_full($p1);
					self::$objectInstances[$classname][$p1] = $instance;
				}

				return self::$objectInstances[$classname][$p1];
			}
			else
			{
				$reflection = new \ReflectionClass($classname_full);
				if($reflection->getConstructor())
					return $reflection->newInstanceArgs($args);
				else
					return $reflection->newInstance();
			}
		}

		/**
		 * Löscht alle gespeicherten Instanzen. Dies ist zum Beispiel wichtig, wenn
		 * eine andere Datenbank ausgewählt wird.
		 * @param string|null $classname Es sollen nur die Instanzen der Klasse $classname gelöscht werden.
		 * @param bool $destruct Wenn true, wird __destruct() aller Objekte manuell ausgeführt, wenn vorhanden.
		 * @return void
		*/

		static function resetInstances($classname=null, $destruct=true)
		{
			if(!isset($classname))
			{
				while(count(self::$objectInstances) > 0)
				{
					foreach(self::$objectInstances as $instanceName=>$instances)
						self::resetInstances($instanceName);
				}
				return;
			}

			if(!isset(self::$objectInstances[$classname])) return;

			foreach(self::$objectInstances[$classname] as $key=>$instance)
			{
				if($destruct && method_exists($instance, "__destruct"))
					$instance->__destruct();
				unset(self::$objectInstances[$classname][$key]);
			}
			unset(self::$objectInstances[$classname]);
		}
	}

	register_shutdown_function(array("\sua\Classes", "resetInstances"));
