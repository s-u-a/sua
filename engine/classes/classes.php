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

	global $objectInstances;
	$objectInstances = array();

	class Classes
	{
		static function Dataset($classname, $p1=false, $write=true)
		{
			global $objectInstances;

			if(!isset($objectInstances)) $objectInstances = array();
			if(!isset($objectInstances[$classname])) $objectInstances[$classname] = array();
			$p1_lower = preg_replace('/[\x00-\x7f]/e', 'strtolower("$0")', $p1);
			if(!isset($objectInstances[$classname][$p1_lower]))
			{
				$instance = new $classname($p1, $write);
				$p1 = $instance->getName();
				$p1_lower = preg_replace('/[\x00-\x7f]/e', 'strtolower("$0")', $p1);
				$objectInstances[$classname][$p1_lower] = $instance;
			}
			elseif($write && $objectInstances[$classname][$p1_lower]->readonly())
			{ # Von Readonly auf Read and write schalten
				$objectInstances[$classname][$p1_lower]->__destruct();
				$objectInstances[$classname][$p1_lower]->__construct($p1, $write);
			}

			return $objectInstances[$classname][$p1_lower];
		}

		static function resetInstances($classname=false, $destruct=true)
		{
			global $objectInstances;

			if(!$classname)
			{
				$status = true;
				while(count($objectInstances) > 0)
				{
					foreach($objectInstances as $instanceName=>$instances)
					{
						if($instanceName == 'EventFile') continue;
						if(!self::resetInstances($instanceName))
							$status = false;
					}
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
		static function User($p1=false, $write=true){ return self::Dataset('User', $p1, $write); }
		static function Alliance($p1=false, $write=true){ return self::Dataset('Alliance', $p1, $write); }
		static function Fleet($p1=false) {
			if($p1 === false) $p1 = str_replace('.', '-', microtime(true));
			return self::Dataset('Fleet', $p1);
		}

		# Serialize
		static function Items(){ return self::Dataset('Items', 'items'); }
		static function Item($id){ return new Item($id); }

		# Eigenes Binaerformat
		static function Galaxy($p1, $write=true){ return self::Dataset('Galaxy', $p1, $write); }

		# SQLite
		static function EventFile() { return new EventFile(); }
		static function Highscores() { return new Highscores(); }
		static function IMFile() { return new IMFile(); }
		static function Message($id=false){ return new Message($id); }
		static function PublicMessage($id=false){ return new PublicMessage($id); }
	}

	register_shutdown_function(array('Classes', 'resetInstances'));
