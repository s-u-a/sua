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
	 * Repräsentiert eine Datenbank und kümmert sich um
	 * deren Konfiguration.
	*/

	class Database
	{
		/**
		 * Die aktuelle Datenbankversion, also die, mit der diese Version des Spiels umgehen kann. Hat eine Datenbank eine andere
		 * Version, so wird die Öffnung verweigert.
		*/
		const currentDatabaseVersion = 9;

		/** Das Datenbankverzeichnis dieser Datenbank
		  * @var string */
		private $directory;

		/** Die Konfigurationsdatei dieser Datenbank
		  * @var Config */
		private $settings;

		/**
		 * @param string $directory Das Datenbankverzeichnis
		*/

		function __construct($directory)
		{
			if(!is_dir($directory))
				throw new DatabaseException("Database directory does not exist.");
			$this->directory = $directory;

			$database_version = $this->getDatabaseVersion();
			if($database_version != self::currentDatabaseVersion)
				throw new DatabaseException("Wrong database version ".$database_version.". Expected ".self::currentDatabaseVersion.". Maybe run update_database?", DatabaseException::WRONG_VERSION);

			if(!is_file($this->directory."/config.xml") || !is_readable($this->directory."/config.xml"))
				throw new DatabaseException("The directory ".$this->directory." does not contain any readable configuration file.");

			$this->config = Classes::Config($this->directory."/config.xml");
		}

		/**
		 * Liefert die Konfiguration der Datenbank (config.xml im Datenbankverzeichnis) zurück.
		 * @return Config
		*/

		function getConfig()
		{
			return $this->config;
		}

		/**
		 * Gibt das Verzeichnis zurück, wo die Datenbank liegt.
		 * @return string
		*/

		function getDirectory()
		{
			return $this->directory;
		}

		/**
		 * Liefert die im Datenbankverzeichnis eingetragene Version zurück.
		 * In der Datenbank wird eine Versionsnummer gespeichert, die Database::currentDatabaseVersion
		 * entsprechen muss, um Konflikte zu vermeiden, wenn das Datenbankschema geändert wird.
		 * Die Version steht in der Datei .version im Datenbankverzeichnis.
		 * @return int
		*/

		function getDatabaseVersion()
		{
			if(is_file($this->getDirectory().'/.version'))
			{
				if(!is_readable($this->getDirectory().'/.version'))
					throw new DatabaseException("Could not read ".$this->getDirectory()."/.version.");
				$current_version = trim(file_get_contents($this->getDirectory().'/.version'));
			}
			elseif(file_exists($this->getDirectory().'/highscores') && !file_exists($this->getDirectory().'/highscores_alliances') && !file_exists($this->getDirectory().'/highscores_alliances2')) $current_version = '4';
			elseif(file_exists($this->getDirectory().'/events') && @sqlite_open($this->getDirectory().'/events')) $current_version = '3';
			elseif(is_dir($this->getDirectory().'/fleets')) $current_version = '2';
			else $current_version = '1';

			return $current_version;
		}
	}