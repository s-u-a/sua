<?php
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
