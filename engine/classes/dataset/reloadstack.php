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

	import("Dataset/SQLite");
	import("Dataset/Classes");

	class ReloadStack extends SQLite
	{
		protected $tables = array("reload_stack" => array("time", "user", "type"));

		function addReload($user, $time, $type)
		{
			if(!$this->status) return false;

			return $this->query("INSERT INTO reload_stack ( time, user, type ) VALUES ( ".$this->escape($time).", ".$this->escape($user).", ".$this->escape($type)." );");
		}

		function getLastUsers()
		{
			if(!$this->status) return false;

			$time = time();
			$result = $this->arrayQuery("SELECT DISTINCT user FROM reload_stack WHERE time < ".$this->escape($time).";");
			$this->query("DELETE FROM reload_stack WHERE time < ".$this->escape($time).";");
			return $result;
		}

		function reset($user, $type=null)
		{
			if(!$this->status) return false;

			if($type !== null)
				return $this->query("DELETE FROM reload_stack WHERE user = ".$this->escape($user)." AND type = ".$this->escape($type).";");
			else
				return $this->query("DELETE FROM reload_stack WHERE user = ".$this->escape($user).";");
		}
	}
