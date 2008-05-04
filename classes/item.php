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

	class Item implements Singleton
	{
		protected $item;
		protected $item_info = false;
		protected $items_instance = false;
		function __construct($id)
		{
			$this->items_instance = Classes::Items();
			$this->item = $id;

			$this->item_info = $this->items_instance->getItemInfo($this->item);
		}

		function getName()
		{
			return $this->item;
		}

		function getInfo($field=null)
		{
			if($this->item_info === false) return false;

			if($field === false || $field === null || $field === array())
				return $this->item_info;
			elseif(is_array($field))
			{
				$ret = array();
				foreach($field as $f)
				{
					if(isset($this->item_info[$f]))
						$ret[$f] = $this->item_info[$f];
				}
				return $ret;
			}
			elseif(isset($this->item_info[$field]))
				return $this->item_info[$field];
			return false;
		}

		function checkDependencies($user, $run_eventhandler=true)
		{
			if(!$user->getStatus()) return false;

			$deps = $this->getDependencies();
			foreach($deps as $id=>$min_level)
			{
				if($user->getItemLevel($id, false, $run_eventhandler) < $min_level)
					return false;
			}
			return true;
		}

		function getDependencies()
		{
			$deps = $this->getInfo('deps');
			$deps_assoc = array();
			foreach($deps as $dep)
			{
				$dep = explode('-', $dep, 2);
				if(count($dep) != 2) continue;
				$deps_assoc[$dep[0]] = $dep[1];
			}
			return $deps_assoc;
		}

		function getType()
		{
			return $this->items_instance->getItemType($this->item);
		}

		static function getItemType($id)
		{
			return Classes::Items()->getItemType($id);
		}
	}
