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
		static function Singleton($classname, $p1=false, $write=true)
		{
			global $objectInstances;

			if(!isset($objectInstances)) $objectInstances = array();
			if(!isset($objectInstances[$classname])) $objectInstances[$classname] = array();
			$p1 = $classname::datasetName($p1);
			if(!isset($objectInstances[$classname][$p1]))
			{
				$instance = new $classname($p1, $write);
				$objectInstances[$classname][$p1] = $instance;
			}
			elseif($write && $objectInstances[$classname][$p1]->readonly())
			{ # Von Readonly auf Read and write schalten
				$objectInstances[$classname][$p1]->__destruct();
				$objectInstances[$classname][$p1]->__construct($p1, $write);
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
		static function User($p1=false, $write=true){ return self::Singleton('User', $p1, $write); }
		static function Alliance($p1=false, $write=true){ return self::Singleton('Alliance', $p1, $write); }
		static function Fleet($p1=false) {
			if($p1 === false) $p1 = str_replace('.', '-', microtime(true));
			return self::Singleton('Fleet', $p1);
		}

		# Serialize
		static function Items(){ return self::Singleton('Items', 'items'); }
		static function Item($id){ return self::Singleton("Item", $id); }

		# Eigenes Binaerformat
		static function Galaxy($p1, $write=true){ return self::Singleton('Galaxy', $p1, $write); }

		# SQLite
		static function EventFile() { return new EventFile(); }
		static function Highscores() { return new Highscores(); }
		static function IMFile() { return new IMFile(); }
		static function Message($id=false){ return new Message($id); }
		static function PublicMessage($id=false){ return new PublicMessage($id); }
		static function ReloadStack() { return new ReloadStack(); }
	}

	register_shutdown_function(array('Classes', 'resetInstances'));
