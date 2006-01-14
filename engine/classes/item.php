<?php
	class Item
	{
		protected $item_info = false;
		protected $items_instance = false;
		function __construct($id)
		{
			$this->items_instance = Classes::Items();
			$this->item = $id;
			
			$this->item_info = $this->items_instance->getItemInfo($this->item);
		}
		
		function getInfo($field=false)
		{
			if($this->item_info === false) return false;
			
			if($field === false)
				return $this->item_info;
			elseif(isset($this->item_info[$field]))
				return $this->item_info[$field];
			return false;
		}
		
		function checkDependencies($user)
		{
			if(!$user->getStatus()) return false;
			
			$deps = $this->getDependencies();
			foreach($deps as $id=>$min_level)
			{
				if($user->getItemLevel($id) < $min_level)
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
	}
	
	class Items
	{
		private $elements = array();
		private $instance = false;
		
		function __construct()
		{
			if(!file_exists(DB_ITEM_DB) || max(
				filemtime(DB_ITEMS.'/gebaeude'),
				filemtime(DB_ITEMS.'/forschung'),
				filemtime(DB_ITEMS.'/roboter'),
				filemtime(DB_ITEMS.'/schiffe'),
				filemtime(DB_ITEMS.'/verteidigung')
				) > filemtime(DB_ITEM_DB))
					$this->refreshItemDatabase();
			
			$this->elements = unserialize(file_get_contents(DB_ITEM_DB));
			$this->elements['ids'] = array();
			foreach($this->elements as $type=>$elements)
			{
				foreach($elements as $id=>$info)
					$this->elements['ids'][$id] = & $this->elements[$type][$id];
			}
		}
		
		function getItemsList($type=false)
		{
			if($type === false) $type = 'ids';
			
			if(!isset($this->elements[$type])) return false;
			return array_keys($this->elements[$type]);
		}
		
		function getItemInfo($id, $type=false)
		{
			if($type === false) $type = 'ids';
			
			if(!isset($this->elements[$type]) || !isset($this->elements[$type][$id]))
				return false;
			return $this->elements[$type][$id];
		}
		
		function getItemType($id)
		{
			foreach($this->elements as $type=>$elements)
			{
				if($type == 'ids') continue;
				if(isset($elements[$id])) return $type;
			}
			return false;
		}
		
		function getName()
		{ # Needed for instances
			return 'items';
		}
		
		function refreshItemDatabase()
		{
			$items = array('gebaeude' => array(), 'forschung' => array(), 'roboter' => array(), 'schiffe' => array(), 'verteidigung' => array(), 'ids' => array());
			if(is_file(DB_ITEMS.'/gebaeude') && is_readable(DB_ITEMS.'/gebaeude'))
			{
				$fh = fopen(DB_ITEMS.'/gebaeude', 'r');
				flock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 1024)))
				{
					$item = explode("\t", $item);
					if(count($item) < 8) continue;
					$items['gebaeude'][$item[0]] = array (
						'name' => $item[1],
						'ress' => explode('.', $item[2]),
						'time' => $item[3],
						'deps' => explode(' ', $item[4]),
						'prod' => explode('.', $item[5]),
						'fields' => $item[6],
						'caption' => $item[7]
					);
					if(trim($item[4]) == '')
						$items['ids'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}

			if(is_file(DB_ITEMS.'/forschung') && is_readable(DB_ITEMS.'/forschung'))
			{
				$fh = fopen(DB_ITEMS.'/forschung', 'r');
				flock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 1024)))
				{
					$item = explode("\t", $item);
					if(count($item) < 6) continue;
					$items['ids'][$item[0]] = array (
						'name' => $item[1],
						'ress' => explode('.', $item[2]),
						'time' => $item[3],
						'deps' => explode(' ', trim($item[4])),
						'caption' => $item[5]
					);
					if(trim($item[4]) == '')
						$items['ids'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}
	
			if(is_file(DB_ITEMS.'/roboter') && is_readable(DB_ITEMS.'/roboter'))
			{
				$fh = fopen(DB_ITEMS.'/roboter', 'r');
				flock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 1024)))
				{
					$item = explode("\t", $item);
					if(count($item) < 6) continue;
					$items['ids'][$item[0]] = array (
						'name' => $item[1],
						'ress' => explode('.', $item[2]),
						'time' => $item[3],
						'deps' => explode(' ', trim($item[4])),
						'caption' => $item[5]
					);
					if(trim($item[4]) == '')
						$items['ids'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}
	
			if(is_file(DB_ITEMS.'/schiffe') && is_readable(DB_ITEMS.'/schiffe'))
			{
				$fh = fopen(DB_ITEMS.'/schiffe', 'r');
				flock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 1024)))
				{
					$item = explode("\t", $item);
					if(count($item) < 11) continue;
					$items['ids'][$item[0]] = array (
						'name' => $item[1],
						'ress' => explode('.', $item[2]),
						'time' => $item[3],
						'deps' => explode(' ', $item[4]),
						'trans' => explode('.', $item[5]),
						'att' => $item[6],
						'def' => $item[7],
						'speed' => $item[8],
						'types' => explode(' ', $item[9]),
						'caption' => $item[10]
					);
					if(trim($item[4]) == '')
						$items['ids'][$item[0]]['deps'] = array();
				}
			}
	
			if(is_file(DB_ITEMS.'/verteidigung') && is_readable(DB_ITEMS.'/verteidigung'))
			{
				$fh = fopen(DB_ITEMS.'/verteidigung', 'r');
				flock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 1024)))
				{
					$item = explode("\t", $item);
					if(count($item) < 8) continue;
					$items['ids'][$item[0]] = array (
						'name' => $item[1],
						'ress' => explode('.', $item[2]),
						'time' => $item[3],
						'deps' => explode(' ', $item[4]),
						'att' => $item[5],
						'def' => $item[6],
						'caption' => $item[7]
					);
					if(trim($item[4]) == '')
						$items['ids'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}
			
			$fh = fopen(DB_ITEM_DB, 'a+');
			if(!$fh) return false;
			flock($fh, LOCK_EX);
			
			fseek($fh, 0, SEEK_SET);
			ftruncate($fh, 0);
			fwrite($fh, serialize($items));
			
			flock($fh, LOCK_UN);
			fclose($fh);
		}
	}
?>