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
	 * @subpackage storage
	*/

	/**
	 * Verwaltet den Event-Stack. Dort steht, wann der Eventhandler welche Flotte
	 * ansehen soll, damit diese an ihrem Ziel ankommt.
	*/

	class EventFile extends SQLite
	{
		protected $tables = array("events" => array("time INT", "fleet"));

		/**
		 * @param integer $time
		 * @param string $id Die ID des Flottenobjekts.
		 * @return null
		*/

		function addNewFleet($time, $id)
		{
			$time = round($time);
			$this->query("INSERT INTO events (time, fleet) VALUES (".$this->escape($time).", ".$this->escape($id).");");
		}

		/**
		 * Liefert die ID der nächsten Flotte, deren Ankunft in der Vergangenheit
		 * liegt, zurück und löscht diese aus der Datenbank.
		 * @return string|boolean Die Flotten-ID oder false, wenn keine Flotte im Stack schon angekommen ist.
		*/

		function removeNextFleet()
		{
			# Naechstes Feld aus der Datenbank lesen
			$field = $this->singleQuery("SELECT * FROM events WHERE time < ".time()." ORDER BY time ASC LIMIT 1;");
			if(!$field) return false;

			# Gefundenes Feld aus der Datenbank loeschen
			$this->query("DELETE FROM events WHERE time = ".$this->escape($field['time'])." AND fleet = ".$this->escape($field['fleet']).";");
			return $field;
		}

		/**
		 * Löscht eine Flotte aus dem Stack, zum Beispiel, weil diese zurückgerufen
		 * wurde.
		 * @param string $fleet
		 * @param integer $time Wenn angegeben, wird nur die Ankunft zu dieser Zeit entfernt, beispielsweise, wenn nur ein Ziel entfernt wurde.
		 * @return null
		*/

		function removeCanceledFleet($fleet, $time=null)
		{
			$query = "DELETE FROM events WHERE fleet = ".$this->escape($fleet);
			if(isset($time))
			{
				$time = round($time);
				$query .= "AND time = ".$this->escape($time);
			}
			$query .= ";";
			$this->query($query);
		}

		/**
		 * Leert den Stack.
		 * @return null
		*/

		function _empty()
		{
			return $this->query("DELETE FROM events;");
		}
	}
