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

	class Items implements Singleton
	{
		private $elements = array();
		private $instance = false;

		function __construct()
		{
			$refresh = false;
			if(!file_exists(global_setting("DB_ITEM_DB"))) $refresh = true;
			else
			{
				$mtimes = array();
				if(is_file(global_setting("DB_ITEMS").'/gebaeude')) $mtimes[] = filemtime(global_setting("DB_ITEMS").'/gebaeude');
				if(is_file(global_setting("DB_ITEMS").'/forschung')) $mtimes[] = filemtime(global_setting("DB_ITEMS").'/forschung');
				if(is_file(global_setting("DB_ITEMS").'/roboter')) $mtimes[] = filemtime(global_setting("DB_ITEMS").'/roboter');
				if(is_file(global_setting("DB_ITEMS").'/schiffe')) $mtimes[] = filemtime(global_setting("DB_ITEMS").'/schiffe');
				if(is_file(global_setting("DB_ITEMS").'/verteidigung')) $mtimes[] = filemtime(global_setting("DB_ITEMS").'/verteidigung');
				if(count($mtimes) > 0 && max($mtimes) > filemtime(global_setting("DB_ITEM_DB")))
					$refresh = true;
			}

			if($refresh)
				$this->refreshItemDatabase();

			$this->elements = unserialize(file_get_contents(global_setting("DB_ITEM_DB")));
		}

		function getItemsList($type=null)
		{
			if($type === false || $type === null)
			{
				return array_merge($this->getItemsList("gebaeude"),
				                   $this->getItemsList("forschung"),
				                   $this->getItemsList("roboter"),
				                   $this->getItemsList("schiffe"),
				                   $this->getItemsList("verteidigung"));
			}

			if(!isset($this->elements[$type])) return false;
			return array_keys($this->elements[$type]);
		}

		function getItemInfo($id, $type=null)
		{
			if($type === false || $type === null)
				$type = $this->getItemType($id);

			if(!isset($this->elements[$type]) || !isset($this->elements[$type][$id]))
				return false;
			$ret = $this->elements[$type][$id];
			return $ret;
		}

		function getItemType($id)
		{
			foreach($this->elements as $type=>$elements)
			{
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
			$items = array('gebaeude' => array(), 'forschung' => array(), 'roboter' => array(), 'schiffe' => array(), 'verteidigung' => array());
			if(is_file(global_setting("DB_ITEMS").'/gebaeude') && is_readable(global_setting("DB_ITEMS").'/gebaeude'))
			{
				$fh = fopen(global_setting("DB_ITEMS").'/gebaeude', 'r');
				Functions::fancyFlock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 65536)))
				{
					$item = explode("\t", $item);
					if(count($item) < 6) continue;
					$items['gebaeude'][$item[0]] = array (
						'ress' => explode('.', $item[1]),
						'time' => $item[2],
						'deps' => explode(' ', $item[3]),
						'prod' => explode('.', $item[4]),
						'fields' => $item[5]
					);
					if(trim($item[3]) == '')
						$items['gebaeude'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}

			if(is_file(global_setting("DB_ITEMS").'/forschung') && is_readable(global_setting("DB_ITEMS").'/forschung'))
			{
				$fh = fopen(global_setting("DB_ITEMS").'/forschung', 'r');
				Functions::fancyFlock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 65536)))
				{
					$item = explode("\t", $item);
					if(count($item) < 4) continue;
					$items['forschung'][$item[0]] = array (
						'ress' => explode('.', $item[1]),
						'time' => $item[2],
						'deps' => explode(' ', trim($item[3]))
					);
					if(trim($item[3]) == '')
						$items['forschung'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}

			if(is_file(global_setting("DB_ITEMS").'/roboter') && is_readable(global_setting("DB_ITEMS").'/roboter'))
			{
				$fh = fopen(global_setting("DB_ITEMS").'/roboter', 'r');
				Functions::fancyFlock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 65536)))
				{
					$item = explode("\t", $item);
					if(count($item) < 4) continue;
					$items['roboter'][$item[0]] = array (
						'ress' => explode('.', $item[1]),
						'time' => $item[2],
						'deps' => explode(' ', trim($item[3]))
					);
					if(trim($item[3]) == '')
						$items['roboter'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}

			if(is_file(global_setting("DB_ITEMS").'/schiffe') && is_readable(global_setting("DB_ITEMS").'/schiffe'))
			{
				$fh = fopen(global_setting("DB_ITEMS").'/schiffe', 'r');
				Functions::fancyFlock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 65536)))
				{
					$item = explode("\t", $item);
					if(count($item) < 9) continue;
					$items['schiffe'][$item[0]] = array (
						'ress' => explode('.', $item[1]),
						'time' => $item[2],
						'deps' => explode(' ', $item[3]),
						'trans' => explode('.', $item[4]),
						'att' => $item[5],
						'def' => $item[6],
						'speed' => $item[7],
						'types' => explode(' ', $item[8])
					);
					$items['schiffe'][$item[0]]['mass'] = round(array_sum($items['schiffe'][$item[0]]['ress'])*.8);
					if(trim($item[3]) == '')
						$items['schiffe'][$item[0]]['deps'] = array();
				}
			}

			if(is_file(global_setting("DB_ITEMS").'/verteidigung') && is_readable(global_setting("DB_ITEMS").'/verteidigung'))
			{
				$fh = fopen(global_setting("DB_ITEMS").'/verteidigung', 'r');
				Functions::fancyFlock($fh, LOCK_SH);
				while($item = preg_replace("/^(.*)(\r\n|\r|\n)$/", "$1", fgets($fh, 65536)))
				{
					$item = explode("\t", $item);
					if(count($item) < 6) continue;
					$items['verteidigung'][$item[0]] = array (
						'ress' => explode('.', $item[1]),
						'time' => $item[2],
						'deps' => explode(' ', $item[3]),
						'att' => $item[4],
						'def' => $item[5]
					);
					if(trim($item[3]) == '')
						$items['verteidigung'][$item[0]]['deps'] = array();
				}
				flock($fh, LOCK_UN);
				fclose($fh);
			}

			$fh = fopen(global_setting("DB_ITEM_DB"), 'a+');
			if(!$fh) return false;
			if(!Functions::fancyFlock($fh, LOCK_EX)) return false;

			fseek($fh, 0, SEEK_SET);
			ftruncate($fh, 0);
			fwrite($fh, serialize($items));

			flock($fh, LOCK_UN);
			fclose($fh);
		}

		static function makeItemsString($items, $html=true, $_i=false)
		{
			self::isort($items);
			$array = array();
			foreach($items as $id=>$count)
			{
				if($count <= 0) continue;
				$str = sprintf(_("%s: %s"), ($_i ? "[item_".$id."]" : _("[item_".$id."]")), F::ths($count));
				if($html) $str = htmlspecialchars($str);
				$array[] = $str;
			}
			return implode(', ', $array);
		}

		static function isort(&$items)
		{
			$copy = $items;
			$items = array();
			$items_obj = Classes::Items();
			foreach($items_obj->getItemsList() as $id)
			{
				if(!isset($copy[$id])) continue;
				$items[$id] = $copy[$id];
				unset($copy[$id]);
			}
			$remaining = array_keys($copy);
			natcasesort($remaining);
			foreach($remaining as $id)
				$items[$id] = $copy[$id];
		}

		static function iadd($a, $b)
		{
			$ret = array();
			foreach(func_get_args() as $arg)
			{
				foreach($arg as $id=>$count)
				{
					if(!isset($ret[$id])) $ret[$id] = 0;
					$ret[$id] += $count;
				}
			}
			foreach($ret as $id=>$count)
			{
				if($count == 0)
					unset($ret[$id]);
			}
			return $ret;
		}

		static function makeItemList($items, $tabs=0)
		{
			$tabs_str = str_repeat("\t", $tabs);
?>
<?=$tabs_str?><dl class="item-list">
<?php
			foreach($items as $id=>$count)
			{
				$item = Classes::Item($id);
?>
<?=$tabs_str?>	<dt class="c-<?=htmlspecialchars($id)?>"><a href="info/description.php?id=<?=htmlspecialchars(urlencode($id))?>&amp;<?=htmlspecialchars(global_setting("URL_SUFFIX"))?>" title="<?=h(_("Genauere Informationen anzeigen"))?>"><?=htmlspecialchars($item->getInfo("name"))?></a></dt>
<?=$tabs_str?>	<dd class="c-<?=htmlspecialchars($id)?>"><?=F::ths($count)?></dd>
<?php
			}
?>
<?=$tabs_str?></dl>
<?php
		}

		/**
		* Konvertiert ein Array, das Items eine Anzahl zuweist, in einen String. Format: (Item-ID ' ' Anzahl ( ' ' Item-ID ' ' Anzahl)* )?
		*/

		static function encodeList($list)
		{
			$ret = array();
			foreach($list as $k=>$v)
				$ret[] = $k." ".$v;
			return implode(" ", $ret);
		}

		/**
		* Konvertiert einen mit encodeList() kodierten String zurueck einem Array ( Item-ID => Anzahl ).
		*/

		static function decodeList($encoded)
		{
			$list = array();
			$encoded_sp = (strlen($encoded) > 0 ? explode(" ", $encoded) : array());
			for($i=0; $i<count($encoded_sp); $i++)
				$list[$encoded_sp[$i]] = (float)$encoded_sp[++$i];
			return $list;
		}
	}
