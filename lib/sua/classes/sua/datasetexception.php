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
	 * In einer Dataset-Funktion ist ein Fehler aufgetreten, zum Beispiel beim Umbenennen
	 * oder Entfernen eines Objekts.
	*/

	class DatasetException extends SuaException
	{
		/** Es wurde noch keine Datenbank per Dataset::setDatabase() gesetzt. */
		const NO_DATABASE = 1;

		/** Der Cache-Wert wurde noch nicht per Dataset::setCacheValue() gesetzt. */
		const CACHE_VALUE_NOT_SET = 2;
	}