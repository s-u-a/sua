<?php
	class EventFile extends SQLite
	{
		protected $tables = array("events" => array("time INT", "fleet"));

		function addNewFleet($time, $id)
		{
			$time = round($time);
			return $this->query("INSERT INTO events (time, fleet) VALUES ('".$this->escape($time)."', '".$this->escape($id)."');");
		}

		function removeNextFleet()
		{
			if(!$this->status) return false;

			# Naechstes Feld aus der Datenbank lesen
			$field = $this->singleQuery("SELECT * FROM events WHERE time < ".time()." ORDER BY time ASC LIMIT 1;");
			if(!$field) return false;

			# Gefundenes Feld aus der Datenbank loeschen
			$this->query("DELETE FROM events WHERE time = '".$this->escape($field['time'])."' AND fleet = '".$this->escape($field['fleet'])."';");
			return $field;
		}

		function removeCanceledFleet($fleet, $time=false)
		{
			$query = "DELETE FROM events WHERE fleet = '".$this->escape($fleet)."'";
			if($time !== false)
			{
				$time = round($time);
				$query .= "AND time = '".$this->escape($time)."'";
			}
			$query .= ";";
			return $this->query($query);
		}

		function _empty()
		{
			return $this->query("DELETE FROM events;");
		}
	}
?>