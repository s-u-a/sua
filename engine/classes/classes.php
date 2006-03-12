<?php
	global $objectInstances;
	$objectInstances = array();
	
	class Classes
	{
		function Dataset($classname, $p1=false, $reset=false)
		{
			global $objectInstances;
			
			if($reset)
			{
				echo "Use deprecated reset method Classes::Dataset. Please use the <a href=\"https://bugs.s-u-a.net/\">Bugtracker</a>.\n";
				ob_end_clean();
				exit();
			}
			
			if(!isset($objectInstances)) $objectInstances = array();
			if(!isset($objectInstances[$classname])) $objectInstances[$classname] = array();
			if(!isset($objectInstances[$classname][$p1]))
			{
				$objectInstances[$classname][$p1] = new $classname($p1);
				$p1_2 = $objectInstances[$classname][$p1]->getName();
				if($p1_2 != $p1)
				{
					if(!isset($objectInstances[$classname][$p1_2]))
						$objectInstances[$classname][$p1_2] = $objectInstances[$classname][$p1];
					unset($objectInstances[$classname][$p1]);
					$p1 = $p1_2;
				}
			}
			
			return $objectInstances[$classname][$p1];
		}
		
		function resetInstances($classname=false)
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
				if(method_exists($instance, '__destruct'))
					$instance->__destruct();
				unset($objectInstances[$classname][$key]);
			}
			unset($objectInstances[$classname]);
			
			return true;
		}
		
		function User($p1=false){ return self::Dataset('User', $p1); }
		function Alliance($p1=false){ return self::Dataset('Alliance', $p1); }
		function Message($p1=false){ return self::Dataset('Message', $p1); }
		function PublicMessage($p1=false){ return self::Dataset('PublicMessage', $p1); }
		function Fleet($p1=false) {
			if($p1 === false) $p1 = str_replace('.', '-', array_sum(explode(' ', microtime())));
			return self::Dataset('Fleet', $p1);
		}
		
		function Items(){ return self::Dataset('Items'); }
		function Item($id){ return new Item($id); }
		
		function Galaxy($p1){ return self::Dataset('Galaxy', $p1); }
		
		function EventFile() { return new Dataset(); }
	}
	
	register_shutdown_function(array('Classes', 'resetInstances'));
?>