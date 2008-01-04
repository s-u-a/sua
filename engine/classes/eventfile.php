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

	class EventFile extends SQLite
	{
		protected $tables = array("events" => array("time INT", "fleet"));

		function addNewFleet($time, $id)
		{
			$time = round($time);
			return $this->query("INSERT INTO events (time, fleet) VALUES (".$this->escape($time).", ".$this->escape($id).");");
		}

		function removeNextFleet()
		{
			if(!$this->status) return false;

			# Naechstes Feld aus der Datenbank lesen
			$field = $this->singleQuery("SELECT * FROM events WHERE time < ".time()." ORDER BY time ASC LIMIT 1;");
			if(!$field) return false;

			# Gefundenes Feld aus der Datenbank loeschen
			$this->query("DELETE FROM events WHERE time = ".$this->escape($field['time'])." AND fleet = ".$this->escape($field['fleet']).";");
			return $field;
		}

		function removeCanceledFleet($fleet, $time=false)
		{
			$query = "DELETE FROM events WHERE fleet = ".$this->escape($fleet);
			if($time !== false)
			{
				$time = round($time);
				$query .= "AND time = ".$this->escape($time);
			}
			$query .= ";";
			return $this->query($query);
		}

		function _empty()
		{
			return $this->query("DELETE FROM events;");
		}
	}
