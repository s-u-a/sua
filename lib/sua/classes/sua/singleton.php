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
	 * Die Funktionen dieses Interfaces werden benötigt, damit Classes::Singleton() die Klassen instanzieren kann.
	 * Singleton darf nicht einfach so abgeschafft werden. Das könnte zu Endlosschleifen werden, denkbar wäre zum Beispiel, dass
	 * der Konstruktor der Planet-Klasse deren Eventhandler aufruft, dieser etwas im User-Objekt macht und das Planet-Objekte erzeugt,
	 * deren Konstruktoren bei Singleton nicht extra aufgerufen werden müssen, und der Eventhandler dann zu Endlosschleifen führen würde.
	*/

	interface Singleton
	{
		/**
		 * Liefert die ID des instanzierten Objekts/Datensets zurück.
		 * @return string
		*/
		public function getName();

		/**
		 * Liefert einen eindeutigen Namen des Datensets mit den Parametern $params zurück. Der Benutzeraccount „test“
		 * lässt sich zum Beispiel auch als „tEst“ referenzieren, da Groß-Klein-Schreibung nicht beachtet wird. Für
		 * beide Werte als $params[0] soll das gleiche zurückgeliefert werden. Manche Datensets benötigen auch mehrere
		 * Paramter zur Referenzierung (Galaxie, System, Planet).
		 * @param $params array
		 * @return string
		*/
		public static function idFromParams($params=null);
	}