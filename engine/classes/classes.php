<?php
	global $objectInstances;
	$objectInstances = array();

	class Classes
	{
		function Dataset($classname, $p1=false, $write=true)
		{
			global $objectInstances;

			if(!isset($objectInstances)) $objectInstances = array();
			if(!isset($objectInstances[$classname])) $objectInstances[$classname] = array();
			if(!isset($objectInstances[$classname][$p1]))
			{
				$instance = new $classname($p1, $write);
				$p1 = $instance->getName();
				$objectInstances[$classname][$p1] = $instance;
			}
			elseif($write && $objectInstances[$classname][$p1]->readonly())
			{ # Von Readonly auf Read and write schalten
				$objectInstances[$classname][$p1]->__destruct();
				$objectInstances[$classname][$p1]->__construct($p1, $write);
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
				/*if($destruct && method_exists($instance, '__destruct'))
					$instance->__destruct();*/
				unset($objectInstances[$classname][$key]);
			}
			unset($objectInstances[$classname]);

			return true;
		}

		# Serialize mit Instanzen und Locking
		function User($p1=false, $write=true){ return self::Dataset('User', $p1, $write); }
		function Alliance($p1=false, $write=true){ return self::Dataset('Alliance', $p1, $write); }
		function Message($p1=false, $write=true){ return self::Dataset('Message', $p1, $write); }
		function PublicMessage($p1=false, $write=true){ return self::Dataset('PublicMessage', $p1, $write); }
		function Fleet($p1=false, $write=true) {
			if($p1 === false) $p1 = str_replace('.', '-', array_sum(explode(' ', microtime())));
			return self::Dataset('Fleet', $p1, $write);
		}

		# Serialize
		function Items(){ return self::Dataset('Items'); }
		function Item($id){ return new Item($id); }

		# Eigenes Binaerformat
		function Galaxy($p1, $write=true){ return self::Dataset('Galaxy', $p1, $write); }

		# SQLite
		function EventFile() { return new EventFile(); }
		function Highscores() { return new Highscores(); }
		function IMFile() { return new IMFile(); }
	}

	register_shutdown_function(array('Classes', 'resetInstances'));
?>