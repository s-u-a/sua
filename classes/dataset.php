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

	interface Dataset
	{
		/**
		 * Liefert die ID des instanzierten Objekts/Datensets zurück.
		 * @return string
		*/
		abstract public function getName();

		/**
		 * Liefert einen eindeutigen Namen des Datensets $name zurück. Der Benutzeraccount „test“ lässt sich
		 * zum Beispiel auch als „tEst“ referenzieren, da Groß-Klein-Schreibung nicht beachtet wird. Für beide
		 * Werte als $name soll das gleiche zurückgeliefert werden.
		 * @param $name string Der Name des Datensets, oder null, wenn ein zufälliger Name erzeugt werden soll.
		 * @return string
		*/
		abstract public static function datasetName($name=null);

		/**
		 * Erzeugt ein Datenset mit dem Namen $name.
		 * @param $name string Null, wenn ein neuer Name erzeugt werden soll.
		 * @return String Die ID des neuen Objekts (normalerweise datasetName($name))
		*/
		abstract static public function create($name=null);

		/**
		 * Löscht das instanzierte Datenset aus der Datenbank.
		 * @return null
		*/
		abstract public function destroy();

		/**
		 * Gibt eine Liste aller Datensets des implementierenden Typs zurück.
		 * @return array(string)
		*/
		abstract static public function getList();

		/**
		 * Gibt die Anzahl existierender Sets des implementierenden Typs zurück (count(getList())).
		 * @return integer
		*/
		abstract static public function getNumber();

		/**
		 * Gibt zurück, ob das angegebene Datenset existiert.
		 * @return boolean
		*/
		abstract static public function exists($name);

		/**
		 * @param $name Die ID des zu instanzierenden Datensets.
		*/
		abstract public function __construct($name=null);
	}