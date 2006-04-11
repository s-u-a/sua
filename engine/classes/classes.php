<?php
	global $objectInstances;
	$objectInstances = array();

	class Classes
	{
		function Dataset($classname, $p1=false)
		{
			global $objectInstances;

			if(!isset($objectInstances)) $objectInstances = array();
			if(!isset($objectInstances[$classname])) $objectInstances[$classname] = array();
			if(!isset($objectInstances[$classname][$p1]))
			{
				$instance = new $classname($p1);
				$p1 = $instance->getName();
				$objectInstances[$p1] = $instance;
			}

			return $objectInstances[$classname][$p1];
		}

		function resetInstances($classname=false, $destruct=true)
		{
			global $objectInstances;

			if(!$classname)
			{
				$status = true;
				foreach($objectInstances as $instanceName=>$instances)
				{
					if($instanceName == 'EventFile') continue;
					if(!self::resetInstances($instanceName))
						$status = false;
				}
				return $status;
			}

			if(!isset($objectInstances[$classname])) return true;

			foreach($objectInstances[$classname] as $key=>$instance)
			{
				if($destruct && method_exists($instance, '__destruct'))
					$instance->__destruct();
				unset($objectInstances[$classname][$key]);
			}
			unset($objectInstances[$classname]);

			return true;
		}

		# Serialize mit Instanzen und Locking
		function User($p1=false){ return self::Dataset('User', $p1); }
		function Alliance($p1=false){ return self::Dataset('Alliance', $p1); }
		function Message($p1=false){ return self::Dataset('Message', $p1); }
		function PublicMessage($p1=false){ return self::Dataset('PublicMessage', $p1); }
		function Fleet($p1=false) {
			if($p1 === false) $p1 = str_replace('.', '-', array_sum(explode(' ', microtime())));
			return self::Dataset('Fleet', $p1);
		}

		# Serialize
		function Items(){ return self::Dataset('Items'); }
		function Item($id){ return new Item($id); }

		# Eigenes Binaerformat
		function Galaxy($p1){ return self::Dataset('Galaxy', $p1); }

		# SQLite
		function EventFile() { return new EventFile(); }
		function Highscores() { return new Highscores(); }
		function IMFile() { return new IMFile(); }
	}

	register_shutdown_function(array('Classes', 'resetInstances'));
?>