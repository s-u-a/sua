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
	 * @subpackage gui
	*/

	namespace sua;
	require_once dirname(dirname(dirname(__FILE__)))."/engine.php";

	/**
	 * Überklasse für Objekte, die sich um das HTML-Gerüst kümmern. Bietet die
	 * Möglichkeit, Optionen zu setzen, die den HTML-Code beeinflussen.
	*/

	abstract class Gui
	{
		/**
		 * Speichert die Optionen.
		 * @var array
		*/
		protected $options = array();

		/**
		 * Wurde init() schon ausgeführt?
		 * @var bool
		*/
		protected $init_run = false;

		/**
		 * Wurde end() schon ausgeführt?
		 * @var bool
		*/
		protected $end_run = false;

		/**
		 * Setzt die GUI-Option $name auf den Wert $value.
		 * @param string $name
		 * @param mixed $value
		 * @return void
		*/
		function setOption($name, $value)
		{
			$this->options[$name] = $value;
		}

		/**
		 * Gibt den Wert der GUI-Option $name zurück.
		 * @param string $name
		 * @return mixed
		*/

		function getOption($name)
		{
			if(!isset($this->options[$name]))
				return null;
			return $this->options[$name];
		}

		/**
		 * Gibt den Teil des HTML-Gerüsts aus, der über dem Inhalt steht.
		 * @return bool Erfolg
		*/
		abstract protected function htmlHead();

		/**
		 * Gibt den Teil des HTML-Gerüsts aus, der unter dem Inhalt steht.
		 * @return bool Erfolg
		*/
		abstract protected function htmlFoot();

		/**
		 * Gibt den Teil des HTML-Gerüsts aus, der über dem Inhalt steht (öffentliche
		 * Funktion). Wenn init() schon einmal ausgeführt wurde, wird false zurückgeliefert.
		 * Wird end() nicht ausgeführt, so geschieht dies automatisch beim Zerstören des
		 * GUI-Objekts.
		 * @return void
		*/

		function init()
		{
			if($this->init_run)
				throw new GuiException("init() has already been run.");
			$this->htmlHead();
			$this->init_run = true;
		}

		/**
		 * Gibt den Teil des HTML-Gerüsts aus, der unter dem Inhalt steht (öffentliche
		 * Funktion). Wenn end() schon einmal ausgeführt wurde, wird false zurückgeliefert.
		 * @return void
		*/

		function end()
		{
			if(!$this->init_run)
				throw new GuiException("init() has not been run.");
			if($this->end_run)
				throw new GuiException("end() has already been run.");
			$this->htmlFoot();
			$this->end_run = true;
		}

		function __destruct()
		{
			if($this->init_run && !$this->end_run)
				$this->end();
		}
	}