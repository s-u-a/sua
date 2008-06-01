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

	/**
	 * @author Candid Dauth
	 * @package sua
	 * @subpackage storage
	*/

	namespace sua;
	require_once dirname(dirname(__FILE__))."/engine.php";

	/**
	 * Repräsentiert ein Item, also ein ebäude, eine Forschung, einen Roboter, ein Schiff
	 * oder eine Verteidigungsanlage.
	 * Ein Item hat einen bestimmten Typ, der über seine Eigenschaften entscheidet.
	 * Die Typen sind: gebaeude, forschung, roboter, schiffe, verteidigung
	*/

	class Item implements StaticInit,Singleton
	{
		/**
		 * Die Standardwerte für die Item-Konfiguration nach Typ. Wird ergänzt,
		 * wenn nicht angegeben oder wenn der Typ Array/Nicht-Array nicht übereinstimmt.
		 * @var array
		*/
		static protected $defaults = array(
			"gebaeude" => array("ress" => array(0,0,0,0), "time" => 0, "deps" => array(), "prod" => array(0,0,0,0,0,0), "fields" => 0),
			"forschung" => array("ress" => array(0,0,0,0), "time" => 0, "deps" => array()),
			"roboter": array("ress" => array(0,0,0,0), "time" => 0, "deps" => array()),
			"schiffe": array("ress" => array(0,0,0,0), "time" => 0, "deps" => array(), "trans" => array(0,0), "att" => 0, "def" => 0, "speed" => 1, "types" => array()),
			"verteidigung": array("ress" => array(0,0,0,0), "time" => 0, "deps" => array(), "att" => 0, "def" => 0)
		);

		/**
		 * Die ID des Items.
		 * @var string
		*/
		protected $item;

		/**
		 * Der Typ des Items.
		 * @var string
		*/
		protected $type;

		/**
		 * Die Werte des Items.
		 * @var array
		*/
		protected $item_info;

		/**
		 * Die Item-Konfiguration, also der Inhalt der Section "items" in der config.xml
		 * des Datenbankverzeichnisses.
		 * @var array
		*/
		protected static $config;

		static function init()
		{
			$db_config = Classes::Database()->getConfig();
			if(!isset($config["items"]))
				self::$config = array();
			else
				self::$config = $config["items"];
		}

		/**
		 * Gibt ein Array aller konfigurierten Item-IDs zurück.
		 * @param string $type Wenn angegeben, werden nur Items dieses Typs angegeben.
		 * @return array(string)
		*/

		static function getList($type=null)
		{
			if(isset($type))
			{
				if(!isset(self::$config[$type]))
					return array();
				else
					return array_keys(self::$config[$type]);
			}
			else
			{
				$return = array();
				foreach(self::$config as $k=>$v)
					$return = array_merge($return, array_keys($v));
				return $return;
			}
		}

		/**
		 * @param string $id Die ID des Items
		 * @param string|null $type Der Typ des Items oder null, damit dieser automatisch festgestellt wird
		 * @return void
		*/

		function __construct($id, $type=null)
		{
			$this->item = $id;

			$config = Classes::Database()->getConfig();
			if(!isset($config["items"]))
				throw new ItemException("No items are configured.");

			$this->type = $type;
			if(is_null($this->type))
				$this->type = self::getItemType($id);
			if(is_null($this->type) || !isset(self::$config[$this->type][$this->item]))
				throw new ItemException("This item is not configured.");

			$this->item_info = &self::$config[$this->type][$this->item];

			if(isset(self::$defaults[$this->type]))
			{
				foreach(self::$defaults[$this->type] as $k=>$v)
				{
					if(!isset($this->item_info[$k]) || is_array($this->item_info[$k]) != is_array($v))
						$this->item_info[$k] = $v;
				}
			}
		}

		/**
		 * @return string
		*/

		function getName()
		{
			return $this->item;
		}

		/**
		 * Gibt den Typ dieses Items zurück.
		 * @return string
		*/

		function getType()
		{
			return $this->type;
		}

		/**
		 * Gibt die Werte dieses Items als assoziatives Array zurück.
		 * @param array|string|null $field Wenn $field angegeben wird, wird nur der Wert des Indexes bzw. der Indexe in $field zurückgegeben.
		 * @return mixed|array
		*/

		function getInfo($field=null)
		{
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

		/**
		 * Überprüft, ob der Benutzer $user alle Abhängigkeiten dieses Gegenstandes
		 * erfüllt hat.
		 * @param User $user
		 * @param bool $run_eventhandler Wird an User->getItemLevel weitergegeben, siehe dort.
		 * @return bool
		*/

		function checkDependencies($user, $run_eventhandler=true)
		{
			$deps = $this->getInfo("deps");
			foreach($deps as $id=>$min_level)
			{
				if($user->getItemLevel($id, false, $run_eventhandler) < $min_level)
					return false;
			}
			return true;
		}

		/**
		 * Gibt den Typ des Items mit der ID $id oder null, wenn das Item nicht gefunden wird, zurück.
		 * @param string $id
		 * @return string|null
		*/

		static function getItemType($id)
		{
			$type = null;
			foreach(self::$config as $k=>$v)
			{
				if(isset($v[$id]))
				{
					$type = $k;
					break;
				}
			}
			return $type;
		}

		/**
		 * Erzeugt eine lesbare Liste aus einem assoziativen Array, dass Items
		 * eine Anzahl zuordnet.
		 * @param array $items ( ID => Anzahl )
		 * @param bool $html Wenn false, wird htmlspecialchars() nicht auf den Text ausgeführt
		 * @param bool $_i Wenn true, wird ein String zurückgegeben, der erst mit l::_i() lokalisiert wird
		 * @return string
		*/

		static function makeItemsString(array $items, $html=true, $_i=false)
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
			return implode(", ", $array);
		}

		/**
		 * Sortiert ein assoziatives Array nach der Reihenfolge, in der die Items
		 * konfiguriert sind.
		 * @param array $items ( ID => Anzahl )
		 * @return void
		*/

		static function isort(&$items)
		{
			$copy = $items;
			$items = array();
			foreach(self::getList() as $id)
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

		/**
		 * Addiert die Items zweier assoziativer Arrays.
		 * @param array $a ( ID => Anzahl )
		 * @param array $b ( ID => Anzahl )
		 * @return array
		*/

		static function iadd(array $a, array $b)
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

		/**
		 * Gibt eine HTML-Definitionsliste mit den Items und deren Anzahl aus einem
		 * assoziativen Array aus.
		 * @param array $items ( ID => Anzahl )
		 * @param int $tabs So viele Tabulatoren werden vor den Code gehängt.
		 * @return void
		*/

		static function makeItemList(array $items, $tabs=0)
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
		 * Konvertiert ein Array, das Items eine Anzahl zuweist, in einen String.
		 * @param array $list ( ID => Anzahl )
		 * @return string
		*/

		static function encodeList($list)
		{
			$ret = array();
			foreach($list as $k=>$v)
				$ret[] = $k." ".$v;
			return implode(" ", $ret);
		}

		/**
		 * Konvertiert einen mit encodeList() kodierten String zurueck.
		 * @param string $encoded
		 * @return array ( ID => Anzahl )
		*/

		static function decodeList($encoded)
		{
			$list = array();
			$encoded_sp = (strlen($encoded) > 0 ? explode(" ", $encoded) : array());
			for($i=0; $i<count($encoded_sp); $i++)
				$list[$encoded_sp[$i]] = (float)$encoded_sp[++$i];
			return $list;
		}

		/**
		 * Planetengröße = Ursprüngliche Größe * (1 + ( Ausbaustufe Ingenieurswissenschaft / Item::getIngtechFactor() ) )
		 * @return int
		*/

		static function getIngtechFactor()
		{
			if(file_exists(global_setting("DB_USE_OLD_INGTECH")))
				return 1;
			elseif(file_exists(global_setting("DB_USE_OLD_ROBTECH")))
				return 2;
			else
				return 10;
		}
	}
